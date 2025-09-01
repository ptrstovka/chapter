<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\CompletedLesson;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Resource;
use App\Support\TextRenderer;
use App\View\Models\VideoSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Inertia\Inertia;

class LessonController
{
    public function show(Request $request, Course $course, Lesson $lesson)
    {
        Gate::authorize('study', $course);

        $user = Auth::user();

        $course->load(['chapters.lessons.video']);
        $course->loadCount('lessons');

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\CompletedLesson> $completions */
        $completions = $user->completedLessons()
            ->whereRelation('lesson.course', 'courses.id', $course->id)
            ->get()
            ->keyBy(fn (CompletedLesson $completion) => $completion->lesson_id);

        $enrollment = $user->findEnrollmentFor($course);

        $lessonNo = 0;
        $currentLesson = $lesson;
        $chapters = $course
            ->chapters
            ->sortBy('position')
            ->values()
            ->map(function (Chapter $chapter, int $idx) use (&$lessonNo, $currentLesson, $course, $completions) {
                return [
                    'no' => $idx + 1,
                    'title' => $chapter->title,
                    'lessons' => $chapter
                        ->lessons
                        ->sortBy('position')
                        ->values()
                        ->map(function (Lesson $lesson) use (&$lessonNo, $currentLesson, $course, $completions) {
                            $lessonNo++;

                            return [
                                'slugId' => $lesson->slug_id,
                                'no' => $lessonNo,
                                'title' => $lesson->title,
                                'isCurrent' => $lesson->is($currentLesson),
                                'duration' => $lesson->video?->getDurationLabel(),
                                'url' => route('lessons.show', [$course->slug, $lesson->slug_id]),
                                'isCompleted' => $completions->has($lesson->id),
                            ];
                        }),
                ];
            });

        return Inertia::render('Courses/CourseLesson', [
            'id' => $lesson->uuid,
            'isCompleted' => $completions->has($lesson->id),
            'completedLessons' => $completions->count(),
            'totalLessons' => $course->lessons_count,
            'remainingLessons' => $course->lessons_count - $completions->count(),
            'progress' => $enrollment->progress,
            'courseSlug' => $course->slug,
            'courseTitle' => $course->title,
            'lessonTitle' => $lesson->title,
            'video' => VideoSource::for($lesson->video),
            'description' => $lesson->description
                ? (new TextRenderer)->toHtml($lesson->description, $lesson->description_type)
                : null,
            'resources' => $lesson->resources->map(fn (Resource $resource) => [
                'name' => $resource->client_file_name,
                'url' => route('resources.show', [$course->slug, $resource->uuid]),
                'size' => Number::fileSize($resource->size),
            ]),
            'chapters' => $chapters,
        ]);
    }
}
