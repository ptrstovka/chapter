<?php


namespace App\Http\Controllers;


use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            ->where(DB::raw('lower(title)'), 'like', '%'.Str::lower($term).'%')
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
