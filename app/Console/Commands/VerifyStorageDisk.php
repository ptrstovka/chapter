<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Throwable;

class VerifyStorageDisk extends Command
{
    protected $signature = 'storage:verify {disk? : The disk to verify (defaults to CONTENT_DISK)}';

    protected $description = 'Verify disk write, metadata, listing, read and delete access using a temporary file';

    public function handle(): int
    {
        $diskName = $this->argument('disk') ?? config('filesystems.content_disk');

        try {
            if (! is_string($diskName) || $diskName === '') {
                throw new InvalidArgumentException('CONTENT_DISK is not configured.');
            }

            if (! is_array(config("filesystems.disks.{$diskName}"))) {
                throw new InvalidArgumentException("Disk [{$diskName}] is not configured.");
            }

            $filesystem = Storage::disk($diskName)->getDriver();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $directory = '.storage-verification/'.Str::uuid();
        $path = $directory.'/probe.txt';
        $contents = 'Chapter storage verification: '.Str::random(64)."\n";
        $needsCleanup = false;
        $failed = false;
        $operation = 'checking the probe path';

        $this->info("Verifying disk [{$diskName}].");

        try {
            if ($filesystem->fileExists($path)) {
                throw new RuntimeException('The probe path already exists. No file was changed; rerun the command.');
            }

            $operation = 'writing';
            $needsCleanup = true;
            $filesystem->write($path, $contents, ['mimetype' => 'text/plain']);
            $this->info('Write: OK');

            $operation = 'checking metadata';
            if (! $filesystem->fileExists($path) || $filesystem->fileSize($path) !== strlen($contents)) {
                throw new RuntimeException('The probe file is missing or its size does not match.');
            }
            $this->info('Metadata: OK');

            $operation = 'listing';
            $listed = false;
            foreach ($filesystem->listContents($directory) as $file) {
                if ($file->isFile() && $file->path() === $path) {
                    $listed = true;
                    break;
                }
            }
            if (! $listed) {
                throw new RuntimeException('The probe file was not returned in the listing.');
            }
            $this->info('List: OK');

            $operation = 'reading';
            $this->verifyContents($filesystem, $path, $contents);
            $this->info('Read: OK');
        } catch (Throwable $exception) {
            $failed = true;
            $this->error("Disk [{$diskName}] failed while {$operation}: {$exception->getMessage()}");
        } finally {
            if ($needsCleanup) {
                try {
                    $filesystem->delete($path);
                    if ($filesystem->fileExists($path)) {
                        throw new RuntimeException('The probe file still exists after deletion.');
                    }
                    $this->info('Delete: OK');
                } catch (Throwable $exception) {
                    $failed = true;
                    $this->error("Disk [{$diskName}] cleanup failed: {$exception->getMessage()}");
                    $this->warn("Check and remove this probe file if it remains: {$path}");
                }
            }
        }

        if ($failed) {
            return self::FAILURE;
        }

        $this->info("Disk [{$diskName}] verified successfully. Probe file removed.");
        $this->line('This checks storage operations only. Public URLs, browser CORS and existing file completeness are not checked.');

        return self::SUCCESS;
    }

    private function verifyContents(FilesystemOperator $filesystem, string $path, string $contents): void
    {
        $stream = $filesystem->readStream($path);

        if (! is_resource($stream)) {
            throw new RuntimeException('Unable to open the probe file stream.');
        }

        try {
            if (stream_get_contents($stream, strlen($contents) + 1) !== $contents) {
                throw new RuntimeException('The probe contents do not match what was written.');
            }
        } finally {
            fclose($stream);
        }
    }
}
