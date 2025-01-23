<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class CalculateVideoDuration implements ShouldQueue
{
    use Queueable, Batchable;

    public function __construct(
        public Video $video
    ) { }

    public function handle(): void
    {
        if ($this->batch()?->canceled()) {
            return;
        }

        $this->video->update([
            'duration_seconds' => FFMpeg::fromDisk('public')
                ->open($this->video->file_path)
                ->getDurationInSeconds(),
        ]);
    }
}
