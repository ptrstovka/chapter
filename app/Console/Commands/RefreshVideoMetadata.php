<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Support\VideoMetadata;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Throwable;

class RefreshVideoMetadata extends Command
{
    protected $signature = 'videos:refresh-metadata {--video=* : Refresh only these video IDs}';

    protected $description = 'Refresh original and HLS sizes from storage metadata without downloading or converting videos';

    public function handle(VideoMetadata $metadata): int
    {
        $ids = $this->option('video');
        foreach ($ids as $id) {
            if (! ctype_digit((string) $id) || ! Video::query()->whereKey($id)->exists()) {
                $this->error('Unknown video ID: '.$id);

                return self::FAILURE;
            }
        }

        $videos = Video::query()->when($ids, fn ($query) => $query->whereKey($ids));
        $total = $videos->count();
        if ($total === 0) {
            $this->info('No videos to refresh.');

            return self::SUCCESS;
        }

        $refreshed = 0;
        $skipped = 0;
        $errors = [];
        $progress = $this->output->isDecorated() ? $this->output->createProgressBar($total) : null;
        if ($progress) {
            $this->info('Refreshing video metadata');
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

            try {
                if ($metadata->refresh($video)) {
                    $refreshed++;
                } else {
                    $skipped++;
                }
            } catch (Throwable $exception) {
                report($exception);
                $errors[] = 'Video '.$video->id.': '.$exception->getMessage();
            }

            $progress?->advance();
        }

        if ($progress) {
            $progress->setMessage($errors ? 'Finished with errors.' : 'Finished.');
            $progress->finish();
            $this->newLine(2);
        }

        $this->line('Refreshed: '.$refreshed.'; skipped: '.$skipped.'; failed: '.count($errors).'.');
        if ($skipped) {
            $this->warn('Skipped videos were busy or changed during the refresh. Rerun the command to refresh them.');
        }
        foreach ($errors as $error) {
            $this->error(OutputFormatter::escape($error));
        }

        return $errors ? self::FAILURE : self::SUCCESS;
    }
}
