<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateCourseDuration implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Course $course
    ) {}

    public function handle(): void
    {
        $this->course->load(['lessons.video']);

        $duration = $this->course->lessons->sum(fn (Lesson $lesson) => $lesson->video?->duration_seconds ?: 0);

        $this->course->update([
            'duration_seconds' => $duration,
        ]);
    }
}
