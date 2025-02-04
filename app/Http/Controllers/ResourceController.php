<?php


namespace App\Http\Controllers;


use App\Models\Course;
use App\Models\Resource;

class ResourceController
{
    public function show(Course $course, Resource $resource)
    {
        return $resource->download();
    }
}
