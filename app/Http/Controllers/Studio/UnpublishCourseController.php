<?php

namespace App\Http\Controllers\Studio;

use App\Models\Course;
use Illuminate\Support\Facades\Gate;

class UnpublishCourseController
{
    public function __invoke(Course $course)
    {
        Gate::authorize('unpublish', $course);

        if ($course->canBeUnpublished()) {
            $course->unpublish();
        }

        return back();
    }
}
