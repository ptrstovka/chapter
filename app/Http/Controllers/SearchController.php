<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'term' => ['required'],
        ]);

        $term = $request->input('term');
        $user = Auth::user();

        $courses = Course::query()
            ->with(['author'])
            ->where('status', CourseStatus::Published)
            ->visibleToLearner($user)
            ->search($term)
            ->limit(6)
            ->get();

        return response()->json($courses->map(fn (Course $course) => [
            'title' => $course->title,
            'url' => route('courses.show', $course),
            'author' => $course->author->name,
            'duration' => $course->getDurationLabel(),
        ]));
    }
}
