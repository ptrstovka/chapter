<?php


namespace App\Http\Controllers;


use App\Models\Course;
use App\Models\Lesson;
use App\View\Models\VideoSource;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class LessonController
{
    public function show(Course $course, Lesson $lesson)
    {
        Gate::authorize('study', $course);

        return Inertia::render('Courses/CourseLesson', [
            'courseTitle' => $course->title,
            'lessonTitle' => $lesson->title,
            'video' => VideoSource::for($lesson->video),
            'description' => $lesson->description,
            'resources' => [], // TODO: Add resources
        ]);
    }
}
