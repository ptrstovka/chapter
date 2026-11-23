<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WorkVideos extends Command
{
    protected $signature = 'videos:work {--once} {--stop-when-empty}';

    protected $description = 'Run one worker for the configured video queue';

    public function handle(): int
    {
        return $this->call('queue:work', [
            'connection' => config('video.queue_connection'),
            '--queue' => config('video.queue'),
            '--timeout' => (int) config('video.timeout') + 60,
            '--memory' => 256,
            '--tries' => 2,
            '--once' => $this->option('once'),
            '--stop-when-empty' => $this->option('stop-when-empty'),
        ]);
    }
}
