<?php


namespace App\View\Layouts;


use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;

class CourseContentLayout extends CourseLayout
{
    public function __construct(Course $course, array $props = [])
    {
        parent::__construct($course, $props);
    }

    public function toLayout(): array
    {
        return [
            ...parent::toLayout(),
            'chapters' => $this->course->chapters
                ->sortBy('position')
                ->values()
                ->map(fn (Chapter $chapter) => [
                    'id' => $chapter->uuid,
                    'position' => $chapter->position,
                    'title' => $chapter->title,
                    'fallbackTitle' => $chapter->getFallbackTitle(),
                    'lessons' => $chapter->lessons->sortBy('position')->values()->map(fn (Lesson $lesson) => [
                        'id' => $lesson->uuid,
                        'title' => $lesson->title,
                        'fallbackTitle' => $lesson->getFallbackTitle(),
                    ]),
                ]),
        ];
    }
}
