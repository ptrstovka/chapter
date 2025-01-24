<?php

namespace App\Console\Commands;

use App\Models\Course;
use Illuminate\Console\Command;

class PublishCourse extends Command
{
    protected $signature = 'course:publish {course}';

    protected $description = 'Publish a course';

    public function handle(): int
    {
        /** @var Course $course */
        $course = Course::findOrFail($this->argument('course'));

        $course->publish();

        return Command::SUCCESS;
    }
}
