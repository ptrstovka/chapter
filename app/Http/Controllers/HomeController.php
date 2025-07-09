<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\View\Models\CourseCard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class HomeController
{
    public function __invoke()
    {
        $user = Auth::user();

        // Courses In Progress
        $inProgress = Course::query()
            ->with(['author'])
            ->withExists([
                'favoritedBy' => fn (Builder $builder) => $builder->where('id', $user->id),
            ])
            ->select('courses.*')
            ->join('course_enrollments', function (JoinClause $join) use ($user) {
                $join->on('course_enrollments.course_id', 'courses.id')
                    ->where('course_enrollments.user_id', $user->id);
            })
            ->whereNull('course_enrollments.completed_at')
            ->where('status', CourseStatus::Published)
            ->latest('course_enrollments.updated_at')
            ->take(4)
            ->get();

        // Latest Courses
        $latest = Course::query()
            ->with(['author'])
            ->withExists([
                'favoritedBy' => fn (Builder $builder) => $builder->where('id', $user->id),
            ])
            ->where('status', CourseStatus::Published)
            ->whereNotIn('id', $inProgress->pluck('id'))
            ->latest()
            ->take(4)
            ->get();

        // Popular Courses
        $popular = Course::query()
            ->with(['author'])
            ->withExists([
                'favoritedBy' => fn (Builder $builder) => $builder->where('id', $user->id),
            ])
            ->select('courses.*')
            ->where('status', CourseStatus::Published)
            ->whereNotIn('courses.id', [
                ...$latest->pluck('id')->all(),
                ...$inProgress->pluck('id')->all(),
            ])
            ->leftJoin('course_enrollments', function (JoinClause $join) use ($user) {
                $join->on('course_enrollments.course_id', 'courses.id')
                    ->where('course_enrollments.user_id', $user->id);
            })
            ->whereNull('course_enrollments.completed_at')
            ->withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->take(4)
            ->get();

        // Discover
        $discoverKey = 'Discover:'.sha1(
            'Courses'.collect([
                ...$inProgress->pluck('id')->all(),
                ...$latest->pluck('id')->all(),
                ...$popular->pluck('id')->all(),
            ])->sort()->values()->join('')
        );

        $discoverIds = Cache::remember($discoverKey, now()->addMinutes(15), function () use ($latest, $inProgress, $popular) {
            return Course::query()
                ->where('status', CourseStatus::Published)
                ->whereNotIn('id', [
                    ...$latest->pluck('id')->all(),
                    ...$inProgress->pluck('id')->all(),
                    ...$popular->pluck('id')->all(),
                ])
                ->inRandomOrder()
                ->take(8)
                ->get(['id'])
                ->pluck('id');
        });

        $discover = collect();
        if ($discoverIds->isNotEmpty()) {
            $discover = Course::query()
                ->with(['author'])
                ->withExists([
                    'favoritedBy' => fn (Builder $builder) => $builder->where('id', $user->id),
                ])
                ->where('status', CourseStatus::Published)
                ->whereIn('id', $discoverIds)
                ->get();
        }

        // All enrollments
        $courseIds = collect([
            ...$inProgress->pluck('id')->all(),
            ...$latest->pluck('id')->all(),
            ...$popular->pluck('id')->all(),
        ])->unique()->values();

        $enrollments = collect();
        if ($courseIds->isNotEmpty()) {
            $enrollments = $user->enrolledCourses()->whereIn('course_id', $courseIds)->get()->keyBy('course_id');
        }

        $mapCourses = fn (Collection $courses) => $courses->map(
            fn (Course $course) => new CourseCard($course, $enrollments->get($course->id))
        );

        return Inertia::render('Home/Home', [
            'inProgress' => $mapCourses($inProgress),
            'latest' => $mapCourses($latest),
            'popular' => $mapCourses($popular),
            'discover' => $mapCourses($discover),
        ]);
    }
}
