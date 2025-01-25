<?php


namespace App\Http\Controllers;


use App\Models\Course;

class EnrollCourseController
{
    public function __invoke(Course $course)
    {
        dd('ok');
    }
}
