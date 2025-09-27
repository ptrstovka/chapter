<?php

namespace App\Jobs;

use App\Models\Course;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SortLessonsWithinCourse implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Course $course
    ) {}

    public function handle(): void
    {
        $this->course->sortLessonsWithinCourse();
    }
}
