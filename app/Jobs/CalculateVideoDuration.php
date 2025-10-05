<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\App;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class CalculateVideoDuration implements ShouldQueue
{
    use Batchable, Queueable;

    public function __construct(
        public Video $video
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->canceled()) {
            return;
        }

        $this->video->update([
            'duration_seconds' => FFMpeg::fromDisk(App::contentDisk())
                ->open($this->video->file_path)
                ->getDurationInSeconds(),
        ]);
    }
}
