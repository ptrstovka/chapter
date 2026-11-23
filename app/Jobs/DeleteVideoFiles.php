<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DeleteVideoFiles implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $disk, public int $videoId, public ?string $original, public ?string $poster) {}

    public function handle(): void
    {
        $storage = Storage::disk($this->disk);
        foreach (array_filter([$this->original, $this->poster]) as $path) {
            if (! $storage->delete($path)) {
                throw new RuntimeException('Unable to delete video file: '.$path);
            }
        }

        if (! $storage->deleteDirectory('video-posters/'.$this->videoId) || ! $storage->deleteDirectory('video-streams/'.$this->videoId)) {
            throw new RuntimeException('Unable to delete video streams: '.$this->videoId);
        }
    }
}
