<?php


namespace App\Http\Controllers;


use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BeginCourseController
{
    public function __invoke(Course $course)
    {
        Gate::authorize('view', $course);

        $user = Auth::user();

        $enrollment = $user->findEnrollmentFor($course);

        abort_if(is_null($enrollment), 400, "You are not enrolled in the course.");

        $latestCompletedLessonPosition = $user
            ->completedLessons()
            ->select(['lessons.position as position'])
            ->join('lessons', 'completed_lessons.lesson_id', 'lessons.id')
            ->where('lessons.course_id', $course->id)
            ->orderByDesc('position')
            ->first()?->position;

        if (is_numeric($latestCompletedLessonPosition)) {
            $lesson = $course->lessons()
                ->where('position', '>', $latestCompletedLessonPosition)
                ->orderBy('position')
                ->first();

            if (! $lesson) {
                $lesson = $course->lessons()->orderByDesc('position')->firstOrFail();
            }
        } else {
            $lesson = $course->lessons()->orderBy('position')->firstOrFail();
        }

        return to_route('lessons.show', [$course->slug, $lesson->slug_id]);
    }
}
