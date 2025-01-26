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

        abort_if(is_null($enrollment), 400, 'You are not enrolled in the course.');

        $nextLesson = $course
            ->lessons()
            ->select('lessons.*')
            ->leftJoin('completed_lessons', 'completed_lessons.lesson_id', 'lessons.id')
            ->whereNull('completed_lessons.id')
            ->orderBy('position')
            ->first();

        if (! $nextLesson) {
            $nextLesson = $course->lessons()->orderBy('position')->first();
        }

        return to_route('lessons.show', [$course->slug, $nextLesson->slug_id]);
    }
}
