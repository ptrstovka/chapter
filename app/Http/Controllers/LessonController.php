<?php


namespace App\Http\Controllers;


use App\Models\Chapter;
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

        $course->load(['chapters.lessons.video']);

        $lessonNo = 0;

        $currentLesson = $lesson;

        $chapters = $course
            ->chapters
            ->sortBy('position')
            ->values()
            ->map(function (Chapter $chapter, int $idx) use (&$lessonNo, $currentLesson, $course) {
                return [
                    'no' => $idx + 1,
                    'title' => $chapter->title,
                    'lessons' => $chapter
                        ->lessons
                        ->sortBy('position')
                        ->values()
                        ->map(function (Lesson $lesson) use (&$lessonNo, $currentLesson, $course) {
                            $lessonNo++;

                            return [
                                'no' => $lessonNo,
                                'title' => $lesson->title,
                                'isCurrent' => $lesson->is($currentLesson),
                                'duration' => $lesson->video?->getDurationLabel(),
                                'url' => route('lessons.show', [$course->slug, $lesson->slug_id]),
                            ];
                        }),
                ];
            });

        return Inertia::render('Courses/CourseLesson', [
            'courseSlug' => $course->slug,
            'courseTitle' => $course->title,
            'lessonTitle' => $lesson->title,
            'video' => VideoSource::for($lesson->video),
            'description' => $lesson->description,
            'resources' => [], // TODO: Add resources
            'chapters' => $chapters,
        ]);
    }
}
