<?php

namespace App\Http\Controllers;

use App\Enums\CourseAccessMode;
use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\View\Models\CourseCard;
use App\View\Models\CoursePresentation;
use App\View\Models\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use StackTrace\Ui\SelectOption;

class CourseController
{
    public function index(Request $request)
    {
        $categories = Category::all()
            ->map(fn (Category $category) => new SelectOption($category->title, $category->slug))
            ->sortBy('label')
            ->values();

        $user = Auth::user();

        $category = null;

        if ($request->has('category')) {
            $category = Category::query()->firstWhere('slug', $request->input('category'));
        }

        $builder = Course::query()
            ->select('courses.*')
            ->with(['author'])
            ->withExists([
                'favoritedBy' => fn (Builder $builder) => $builder->where('id', $user->id),
            ])
            ->when($category, function (Builder $builder, Category $category) {
                $builder->whereBelongsTo($category);
            })
            ->when($request->boolean('hideCompleted'), function (Builder $builder) use ($user) {
                $builder
                    ->leftJoin('course_enrollments', function (JoinClause $join) use ($user) {
                        $join->on('course_enrollments.course_id', 'courses.id')
                            ->where('course_enrollments.user_id', $user->id);
                    })
                    ->whereNull('course_enrollments.completed_at');
            })
            ->when(
                $request->boolean('onlyFavorite'),
                fn (Builder $builder) => $builder->whereHas('favoritedBy', fn (Builder $builder) => $builder->where('id', $user->id))
            )
            ->visibleToLearner($user)
            ->where('status', CourseStatus::Published);

        match ($request->input('sort')) {
            'title-asc' => $builder->orderBy('title'),
            'title-desc' => $builder->orderByDesc('title'),
            'popular' => $builder->withCount('enrollments')->orderByDesc('enrollments_count'),
            default => $builder->latest(),
        };

        $courses = $builder
            ->paginate(36)
            ->withQueryString();

        $enrollments = collect();

        if ($courses->collect()->isNotEmpty()) {
            $enrollments = $user
                ->enrolledCourses()
                ->whereIn('course_id', $courses->collect()->pluck('id'))
                ->get()
                ->keyBy(fn (CourseEnrollment $enrollment) => $enrollment->course_id);
        }

        return Inertia::render('Courses/CourseList', [
            'categories' => $categories,
            'category' => $category?->title,
            'courses' => Paginator::make(
                $courses->through(fn (Course $course) => new CourseCard($course, $enrollments->get($course->id)))
            ),
        ]);
    }

    public function show(Course $course, CoursePresentation $presentation)
    {
        $user = Auth::user();

        abort_unless($course->status === CourseStatus::Published && $course->isVisibleToLearner($user), 404);

        $enrollment = $user->findEnrollmentFor($course);
        $canEnroll = Gate::allows('enroll', $course);
        $hasPendingAccessRequest = $course
            ->accessRequests()
            ->whereBelongsTo($user)
            ->where('status', 'pending')
            ->exists();

        return Inertia::render('Courses/CourseDetail', [
            ...$presentation->detail($course, $course),
            'access' => [
                'mode' => $course->access_mode->value,
                'canEnroll' => $canEnroll,
                'canRequest' => $course->access_mode === CourseAccessMode::RequestOnly && ! $canEnroll && ! $hasPendingAccessRequest,
                'hasPendingRequest' => $hasPendingAccessRequest,
            ],
            'enrollment' => $enrollment ? [
                'isCompleted' => $enrollment->isCompleted(),
                'progress' => $enrollment->progress,
                'completedLessons' => $user->completedLessons()->whereRelation('lesson.course', 'id', $course->id)->count(),
            ] : null,
        ]);
    }
}
