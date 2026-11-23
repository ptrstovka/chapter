<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Support\VideoProcessing;
use App\Support\VideoSettings;
use Illuminate\Console\Command;
use Throwable;

class ProcessVideos extends Command
{
    protected $signature = 'videos:process {--video=* : Process only these video IDs} {--reprocess : Reprocess ready videos using the current settings}';

    protected $description = 'Prepare existing videos, or convert them to HLS using the configured video settings';

    public function handle(VideoProcessing $processing, VideoSettings $settings): int
    {
        $ids = $this->option('video');
        foreach ($ids as $id) {
            if (! ctype_digit((string) $id) || ! Video::query()->whereKey($id)->exists()) {
                $this->error('Unknown video ID: '.$id);

                return self::FAILURE;
            }
        }
        $options = $settings->snapshot();
        $failed = 0;
        $completed = 0;
        $skipped = 0;

        foreach (Video::query()->when($ids, fn ($query) => $query->whereKey($ids))->lazyById() as $video) {
            if (($video->hls_playlist_path && ! $options['hls'])
                || (! $this->option('reprocess') && $video->isReady() && (! $options['hls'] || $video->hls_playlist_path))) {
                $skipped++;

                continue;
            }
            $this->line('Processing video '.$video->id.'…');
            try {
                $attempt = $processing->prepare($video, $options);
                $processing->process($video->id, $attempt);
                $completed++;
                $this->info('Video '.$video->id.' ready.');
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
                $this->error('Video '.$video->id.': '.$exception->getMessage());
            }
        }

        $this->line("Completed: {$completed}; skipped: {$skipped}; failed: {$failed}.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
