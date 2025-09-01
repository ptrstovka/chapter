<?php


namespace App\Http\Controllers\Studio;


use App\Models\Course;
use Illuminate\Support\Facades\Gate;

class PublishCourseController
{
    public function __invoke(Course $course)
    {
        Gate::authorize('publish', $course);

        if ($course->canBePublished()) {
            $course->publish();
        }

        return back();
    }
}
