<?php


namespace App\Http\Controllers;


use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BeginCourseController
{
    public function __invoke(Course $course)
    {
        Gate::authorize('view', $course);

        $enrollment = Auth::user()->findEnrollmentFor($course);

        abort_if(is_null($enrollment), 400, "You are not enrolled in the course.");

        // TODO: Search for latest not completed lesson
        /** @var \App\Models\Chapter $chapter */
        $chapter = $course->chapters()->orderBy('position')->firstOrFail();
        /** @var \App\Models\Lesson $lesson */
        $lesson = $chapter->lessons()->orderBy('position')->firstOrFail();

        return to_route('lessons.show', [$course->slug, $lesson->slug_id]);
    }
}
