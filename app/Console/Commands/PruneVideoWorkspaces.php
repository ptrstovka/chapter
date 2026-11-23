<?php

namespace App\Console\Commands;

use App\Enums\VideoProcessingStatus;
use App\Models\Video;
use App\Support\VideoProcessing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PruneVideoWorkspaces extends Command
{
    protected $signature = 'videos:prune-workspaces';

    protected $description = 'Remove abandoned video workspaces after 24 hours';

    public function handle(): int
    {
        $root = config('video.work_directory');
        if (! is_dir($root)) {
            return self::SUCCESS;
        }
        foreach (File::directories($root) as $directory) {
            if (filemtime($directory) > now()->subDay()->timestamp || ! preg_match('/^(\d+)-([a-f0-9-]{36})$/', basename($directory), $matches)) {
                continue;
            }
            $lock = Cache::lock(VideoProcessing::lockKey((int) $matches[1]), 60);
            if (! $lock->get()) {
                continue;
            }
            try {
                $prefix = 'video-streams/'.$matches[1].'/'.$matches[2];
                if (! Video::query()->where('hls_playlist_path', $prefix.'/master.m3u8')->exists()) {
                    Storage::disk(App::contentDisk())->deleteDirectory($prefix);
                }
                $poster = 'video-posters/'.$matches[1].'/'.$matches[2].'.jpg';
                if (! Video::query()->where('poster_image_file_path', $poster)->exists()) {
                    Storage::disk(App::contentDisk())->delete($poster);
                }
                Video::query()->whereKey($matches[1])->where('processing_attempt', $matches[2])
                    ->where('processing_status', VideoProcessingStatus::Processing)
                    ->update(['processing_status' => VideoProcessingStatus::Failed, 'processing_error' => 'Video processing was interrupted.']);
                File::deleteDirectory($directory);
            } finally {
                $lock->release();
            }
        }

        return self::SUCCESS;
    }
}
