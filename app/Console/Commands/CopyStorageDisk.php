<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use InvalidArgumentException;
use League\Flysystem\UnableToRetrieveMetadata;
use RuntimeException;
use Throwable;

class CopyStorageDisk extends Command
{
    protected $signature = 'storage:copy
                            {source : The configured source disk}
                            {destination : The configured destination disk}';

    protected $description = 'Copy all files between disks, preserving paths and verifying contents with SHA-256';

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
        $this->line('Identical files are skipped; different destination files are overwritten. No files are deleted.');
        $this->line('Keep uploads, queue jobs and scheduled file changes stopped until the disk switch is complete.');

        $copied = 0;
        $skipped = 0;
        $failed = 0;

        try {
            foreach ($source->getDriver()->listContents('', true) as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $path = $file->path();

                try {
                    if ($this->copyFile($source, $destination, $path)) {
                        $copied++;
                    } else {
                        $skipped++;
                    }
                } catch (Throwable $exception) {
                    $failed++;
                    $this->error("Failed [{$sourceName}:{$path}] -> [{$destinationName}:{$path}]: {$exception->getMessage()}");
                }
            }
        } catch (Throwable $exception) {
            $failed++;
            $this->error("Unable to finish listing [{$sourceName}]: {$exception->getMessage()}");
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
        $this->line("Checking {$path} (".Number::fileSize($size).')');
        $checksum = $this->checksum($source, $path);

        if ($destination->exists($path)
            && $destination->size($path) === $size
            && $this->checksum($destination, $path) === $checksum) {
            $this->line("Skipped identical file: {$path}");

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

        $this->line("Copying: {$path}");

        try {
            if (! $destination->writeStream($path, $stream, ['mimetype' => $mimeType])) {
                throw new RuntimeException('Unable to write the destination file.');
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $this->line("Verifying: {$path}");

        if ($destination->size($path) !== $size || $this->checksum($destination, $path) !== $checksum) {
            throw new RuntimeException('Destination size or SHA-256 checksum does not match the source.');
        }

        $this->line("Copied and verified: {$path}");

        return true;
    }

    private function checksum(FilesystemAdapter $disk, string $path): string
    {
        $checksum = $disk->checksum($path, ['checksum_algo' => 'sha256']);

        if ($checksum === false) {
            throw new RuntimeException('Unable to calculate the SHA-256 checksum.');
        }

        return $checksum;
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
