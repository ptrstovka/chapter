<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class ExtractVideoPoster implements ShouldQueue
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

        $name = Str::random(32).'.jpeg';

        FFMpeg::fromDisk('public')
            ->open($this->video->file_path)
            ->getFrameFromSeconds(0)
            ->export()
            ->toDisk('public')
            ->save("video-posters/{$name}");

        $this->video->update(['poster_image_file_path' => "video-posters/{$name}"]);
    }
}
