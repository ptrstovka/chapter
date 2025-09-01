<?php

namespace App\Console\Commands;

use App\Jobs\ImportCourse as ImportJob;
use App\Models\Course;
use Illuminate\Console\Command;

class ImportCourse extends Command
{
    protected $signature = 'course:import {dir} {--skip-publish}';

    protected $description = 'Import course from folder';

    public function handle(): int
    {
        $dir = $this->argument('dir');

        $slug = basename($dir);

        if (Course::query()->where('slug', $slug)->exists()) {
            $this->info("Course [$slug] already imported");

            return Command::SUCCESS;
        }

        dispatch(new ImportJob(
            folder: $this->argument('dir'),
            publish: ! $this->option('skip-publish')
        ));

        return Command::SUCCESS;
    }
}
