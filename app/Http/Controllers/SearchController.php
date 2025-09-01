<?php


namespace App\Http\Controllers;


use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Http\Request;

class SearchController
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'term' => ['required'],
        ]);

        $term = $request->input('term');

        $courses = Course::query()
            ->with(['author'])
            ->where('status', CourseStatus::Published)
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
