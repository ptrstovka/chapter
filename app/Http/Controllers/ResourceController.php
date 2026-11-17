<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Resource;
use Illuminate\Support\Facades\Gate;

class ResourceController
{
    public function show(Course $course, Resource $resource)
    {
        abort_unless($resource->course_id === $course->id, 404);

        Gate::authorize('study', $course);

        return $resource->download();
    }
}
