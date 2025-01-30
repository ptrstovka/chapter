<?php

namespace App\Console\Commands;

use App\Jobs\ImportCourse as ImportJob;
use Illuminate\Console\Command;

class ImportCourse extends Command
{
    protected $signature = 'course:import {dir} {--skip-publish}';

    protected $description = 'Import course from folder';

    public function handle(): int
    {
        dispatch(new ImportJob(
            folder: $this->argument('dir'),
            publish: !$this->option('skip-publish')
        ));

        return Command::SUCCESS;
    }
}
