<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use League\Flysystem\UnableToRetrieveMetadata;
use RuntimeException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Throwable;

class CopyStorageDisk extends Command
{
    protected $signature = 'storage:copy
                            {source : The configured source disk}
                            {destination : The configured destination disk}';

    protected $description = 'Copy all files between disks, preserving paths and checking file sizes';

    public function handle(): int
    {
        $sourceName = $this->argument('source');
        $destinationName = $this->argument('destination');

        try {
            foreach ([$sourceName, $destinationName] as $name) {
                if (! is_array(config("filesystems.disks.{$name}"))) {
                    throw new InvalidArgumentException("Disk [{$name}] is not configured.");
                }
            }

            if ($sourceName === $destinationName) {
                throw new InvalidArgumentException('Source and destination must be different disks.');
            }

            $source = Storage::disk($sourceName);
            $destination = Storage::disk($destinationName);

            if ($this->disksOverlap($source, $destination)) {
                throw new InvalidArgumentException('Source and destination storage locations must not overlap.');
            }
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Copying [{$sourceName}] to [{$destinationName}].");
        $this->line('Destination files with matching sizes are skipped; different sizes are overwritten. No files are deleted.');
        $this->line('Keep uploads, queue jobs and scheduled file changes stopped until the disk switch is complete.');

        $this->line('Counting source files…');
        $total = 0;
        try {
            foreach ($source->getDriver()->listContents('', true) as $file) {
                if ($file->isFile()) {
                    $total++;
                }
            }
        } catch (Throwable $exception) {
            $this->error(OutputFormatter::escape("Unable to finish listing [{$sourceName}]: {$exception->getMessage()}"));

            return self::FAILURE;
        }

        if ($total === 0) {
            $this->info('No files to copy.');

            return self::SUCCESS;
        }

        $progress = $this->output->isDecorated() ? $this->output->createProgressBar($total) : null;
        if ($progress) {
            $this->newLine();
            $progress->setFormat(" %current%/%max% [<fg=green>%bar%</>] %percent:3s%%\n %message%");
            $progress->setBarCharacter('━');
            $progress->setEmptyBarCharacter('─');
            $progress->setProgressCharacter('╸');
            $progress->setMessage('Starting…');
            $progress->start();
        }

        $copied = 0;
        $skipped = 0;
        $failed = 0;
        $listingFailed = false;

        try {
            foreach ($source->getDriver()->listContents('', true) as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $path = $file->path();
                $progress?->setMessage('File: '.OutputFormatter::escape($path));
                $progress?->display();

                try {
                    if ($this->copyFile($source, $destination, $path)) {
                        $copied++;
                    } else {
                        $skipped++;
                    }
                } catch (Throwable $exception) {
                    $failed++;
                    $progress?->clear();
                    $this->error(OutputFormatter::escape("Failed [{$sourceName}:{$path}] -> [{$destinationName}:{$path}]: {$exception->getMessage()}"));
                    $progress?->display();
                }

                $progress?->advance();
            }
        } catch (Throwable $exception) {
            $failed++;
            $listingFailed = true;
            $progress?->clear();
            $this->error(OutputFormatter::escape("Unable to finish listing [{$sourceName}]: {$exception->getMessage()}"));
        }

        if ($progress) {
            if ($listingFailed) {
                $progress->setMessage('Interrupted while listing files.');
                $progress->display();
            } else {
                $progress->setMessage($failed ? 'Finished with errors.' : 'Finished.');
                $progress->finish();
            }
            $this->newLine(2);
        }

        $this->info("Copied: {$copied}; skipped: {$skipped}; failed: {$failed}.");

        if ($failed > 0) {
            $this->error('Copy incomplete. Resolve the errors and rerun this command before switching disks.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function copyFile(FilesystemAdapter $source, FilesystemAdapter $destination, string $path): bool
    {
        $size = $source->size($path);
        if ($destination->exists($path)
            && $destination->size($path) === $size) {
            return false;
        }

        try {
            $mimeType = $source->mimeType($path) ?: 'application/octet-stream';
        } catch (UnableToRetrieveMetadata) {
            $mimeType = 'application/octet-stream';
        }

        $stream = $source->readStream($path);

        if (! is_resource($stream)) {
            throw new RuntimeException('Unable to open the source file stream.');
        }

        try {
            if (! $destination->writeStream($path, $stream, ['mimetype' => $mimeType])) {
                throw new RuntimeException('Unable to write the destination file.');
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if ($destination->size($path) !== $size) {
            throw new RuntimeException('Destination size does not match the source.');
        }

        return true;
    }

    private function disksOverlap(FilesystemAdapter $source, FilesystemAdapter $destination): bool
    {
        $sourceConfig = $source->getConfig();
        $destinationConfig = $destination->getConfig();

        if ($sourceConfig['driver'] === 'local' && $destinationConfig['driver'] === 'local') {
            $sourceRoot = realpath($source->path('')) ?: $source->path('');
            $destinationRoot = realpath($destination->path('')) ?: $destination->path('');
        } elseif ($sourceConfig['driver'] === 's3' && $destinationConfig['driver'] === 's3'
            && $sourceConfig['bucket'] === $destinationConfig['bucket']
            && rtrim($sourceConfig['endpoint'] ?? '', '/') === rtrim($destinationConfig['endpoint'] ?? '', '/')) {
            $sourceRoot = $source->path('');
            $destinationRoot = $destination->path('');
        } else {
            return false;
        }

        $sourceRoot = rtrim($sourceRoot, '/');
        $destinationRoot = rtrim($destinationRoot, '/');

        if ($sourceRoot === '' || $destinationRoot === '') {
            return true;
        }

        $sourceRoot .= '/';
        $destinationRoot .= '/';

        return str_starts_with($sourceRoot, $destinationRoot) || str_starts_with($destinationRoot, $sourceRoot);
    }
}
