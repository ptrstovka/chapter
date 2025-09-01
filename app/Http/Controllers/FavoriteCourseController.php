<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Support\Facades\Auth;

class FavoriteCourseController
{
    public function store(Course $course)
    {
        $user = Auth::user();

        if ($user->favoriteCourses()->where('id', $course->id)->doesntExist()) {
            $user->favoriteCourses()->attach($course);
        }

        return back();
    }

    public function destroy(Course $course)
    {
        $user = Auth::user();

        if ($user->favoriteCourses()->where('id', $course->id)->exists()) {
            $user->favoriteCourses()->detach($course);
        }

        return back();
    }
}
