<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Support\VideoProcessing;
use App\Support\VideoSettings;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
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
        $errors = [];
        $videos = Video::query()->when($ids, fn ($query) => $query->whereKey($ids));
        $total = $videos->count();

        if ($total === 0) {
            $this->info('No videos to process.');

            return self::SUCCESS;
        }

        $progress = $this->output->isDecorated() ? $this->output->createProgressBar($total) : null;
        if ($progress) {
            $this->info($options['hls'] ? 'Converting videos to HLS' : 'Processing videos');
            $this->newLine();
            $progress->setFormat(" %current%/%max% [<fg=green>%bar%</>] %percent:3s%%\n %message%");
            $progress->setBarCharacter('━');
            $progress->setEmptyBarCharacter('─');
            $progress->setProgressCharacter('╸');
            $progress->setMessage('Starting…');
            $progress->start();
        }

        foreach ($videos->lazyById() as $video) {
            $progress?->setMessage('Video #'.$video->id);
            $progress?->display();

            if (($video->hls_playlist_path && ! $options['hls'])
                || (! $this->option('reprocess') && $video->isReady() && (! $options['hls'] || $video->hls_playlist_path))) {
                $skipped++;
            } else {
                try {
                    $attempt = $processing->prepare($video, $options);
                    $processing->process($video->id, $attempt);
                    $completed++;
                } catch (Throwable $exception) {
                    $failed++;
                    report($exception);
                    $errors[] = 'Video '.$video->id.': '.$exception->getMessage();
                }
            }

            $progress?->advance();
        }

        if ($progress) {
            $progress->setMessage($failed ? 'Finished with errors.' : 'Finished.');
            $progress->finish();
            $this->newLine(2);
        }

        $this->line("Completed: {$completed}; skipped: {$skipped}; failed: {$failed}.");

        foreach ($errors as $error) {
            $this->error(OutputFormatter::escape($error));
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
