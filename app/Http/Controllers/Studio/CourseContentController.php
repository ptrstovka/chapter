<?php

namespace App\Http\Controllers\Studio;

use App\Models\Course;
use App\View\Layouts\CourseContentLayout;
use Inertia\Inertia;

class CourseContentController
{
    public function __invoke(Course $course)
    {
        return Inertia::render('Studio/CourseContent', CourseContentLayout::make($course));
    }
}
