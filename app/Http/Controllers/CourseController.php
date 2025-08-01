<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\View\Models\CourseCard;
use App\View\Models\Paginator;
use App\View\Models\VideoSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CourseController
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Course::class);

        $categories = Category::all()->map(fn (Category $category) => [
            'value' => $category->slug,
            'title' => $category->title,
        ])->sortBy('title')->values();

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

    public function show(Course $course)
    {
        Gate::authorize('view', $course);

        $course->load('chapters.lessons.video');

        $user = Auth::user();

        $enrollment = $user->findEnrollmentFor($course);

        return Inertia::render('Courses/CourseDetail', [
            'id' => $course->uuid,
            'slug' => $course->slug,
            'title' => $course->title,
            'trailer' => VideoSource::for($course->trailer),
            'description' => $course->description,
            'enrollment' => $enrollment ? [
                'isCompleted' => $enrollment->isCompleted(),
                'progress' => $enrollment->progress,
                'completedLessons' => $user->completedLessons()->whereRelation('lesson.course', 'id', $course->id)->count(),
            ] : null,
            'author' => [
                'name' => $course->author->name,
                'avatarUrl' => $course->author->getAvatarUrl(),
                'bio' => $course->author->bio,
            ],
            'chapters' => $course->chapters->sortBy('position')->values()->map(fn (Chapter $chapter) => [
                'id' => $chapter->uuid,
                'title' => $chapter->title,
                'lessons' => $chapter->lessons->sortBy('position')->values()->map(fn (Lesson $lesson) => [
                    'id' => $lesson->uuid,
                    'title' => $lesson->title,
                    'duration' => $lesson->video?->getDurationLabel(),
                ]),
            ]),
        ]);
    }
}
