<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\View\Models\CoursePresentation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LessonController
{
    public function show(Course $course, Lesson $lesson, CoursePresentation $presentation): Response
    {
        abort_unless($lesson->course_id === $course->id, 404);

        Gate::authorize('study', $course);

        $user = Auth::user();
        $completedLessonIds = $user->completedLessons()
            ->whereRelation('lesson.course', 'courses.id', $course->id)
            ->pluck('lesson_id')
            ->all();

        return Inertia::render('Courses/CourseLesson', [
            ...$presentation->lesson($course, $course, $lesson, completedLessonIds: $completedLessonIds),
            'progress' => $user->findEnrollmentFor($course)->progress,
        ]);
    }
}
