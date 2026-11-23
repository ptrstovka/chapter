<?php

namespace App\Http\Middleware;

use App\Models\Course;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudioCourseAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $course = $request->route('course');
        if (! $course instanceof Course) {
            return $next($request);
        }
        Gate::authorize('view', $course);
        foreach (['chapter', 'lesson'] as $name) {
            $child = $request->route($name);
            if ($child) {
                abort_unless($child->course_id === $course->id, 404);
            }
        }
        if ($request->route('lesson') && $request->route('chapter')) {
            abort_unless($request->route('lesson')->chapter_id === $request->route('chapter')->id, 404);
        }
        if (! $request->routeIs(
            'studio.courses.update', 'studio.courses.destroy',
            'studio.course.chapters.store', 'studio.course.chapters.update', 'studio.course.chapters.destroy', 'studio.course.chapters.sort',
            'studio.course.lessons.store', 'studio.course.lessons.update', 'studio.course.lessons.destroy', 'studio.course.lessons.move',
        )) {
            return $next($request);
        }

        return DB::transaction(function () use ($course, $request, $next) {
            $current = Course::query()->lockForUpdate()->findOrFail($course->id);
            Gate::authorize('update', $current);
            abort_unless($current->canBeUpdated(), 409, __('This course cannot be edited.'));
            $request->route()->setParameter('course', $current);
            foreach (['chapter', 'lesson'] as $name) {
                $child = $request->route($name);
                if ($child) {
                    $child->refresh();
                    abort_unless($child->course_id === $current->id, 404);
                }
            }
            if ($request->route('lesson') && $request->route('chapter')) {
                abort_unless($request->route('lesson')->chapter_id === $request->route('chapter')->id, 404);
            }

            return $next($request);
        });
    }
}
