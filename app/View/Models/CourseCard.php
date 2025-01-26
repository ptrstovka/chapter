<?php


namespace App\View\Models;


use App\Models\Course;
use App\Models\CourseEnrollment;

class CourseCard extends Model
{
    public function __construct(
        protected Course $course,
        protected ?CourseEnrollment $enrollment
    ) { }

    public function toView(): array
    {
        return [
            'title' => $this->course->title,
            'url' => route('courses.show', $this->course->slug),
            'coverImageUrl' => $this->course->getCoverImageUrl(),
            'duration' => $this->course->getDurationLabel(),
            'author' => [
                'name' => $this->course->author->name,
            ],
            'enrollment' => $this->enrollment ? [
                'isCompleted' => $this->enrollment->isCompleted(),
                'progress' => $this->enrollment->progress,
            ] : null,
        ];
    }
}
