<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BeginCourseController
{
    public function __invoke(Course $course)
    {
        Gate::authorize('study', $course);

        $user = Auth::user();

        $enrollment = $user->findEnrollmentFor($course);

        abort_if(is_null($enrollment), 400, 'You are not enrolled in the course.');

        $nextLesson = $course
            ->lessons()
            ->select('lessons.*')
            ->leftJoin('completed_lessons', function (JoinClause $join) use ($user): void {
                $join->on('completed_lessons.lesson_id', 'lessons.id')
                    ->where('completed_lessons.user_id', $user->id);
            })
            ->whereNull('completed_lessons.id')
            ->orderBy('position_within_course')
            ->first();

        if (! $nextLesson) {
            $nextLesson = $course->lessons()->orderBy('position_within_course')->first();
        }

        return to_route('lessons.show', [$course->slug, $nextLesson->slug_id]);
    }
}
