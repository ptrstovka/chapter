<?php

namespace App\Jobs;

use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use LogicException;

class PublishCourse implements ShouldQueue
{
    use Queueable;

    public function __construct(public Course $course) {}

    public function handle(): void
    {
        DB::transaction(function () {
            $course = Course::query()->lockForUpdate()->findOrFail($this->course->id);
            if ($course->status !== CourseStatus::Publishing || ! $course->hasReadyVideos()) {
                throw new LogicException('The course videos are not ready to publish.');
            }
            $course->update(['status' => CourseStatus::Published, 'has_been_published' => true]);
        });
    }
}
