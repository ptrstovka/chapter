<?php

namespace App\Support;

use App\Enums\VideoFormat;
use App\Enums\VideoProcessingStatus;
use App\Jobs\ProcessVideo;
use App\Models\Video;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class VideoProcessing
{
    public function __construct(protected VideoEncoder $encoder) {}

    public static function lockKey(int $id): string
    {
        return 'video-processing:'.$id;
    }

    /** @param array{hls: bool, keep_original: bool, qualities: array<int, int>} $options */
    public function prepare(Video $video, array $options, bool $retry = false): string
    {
        return Cache::lock(static::lockKey($video->id), 30)->block(1, function () use ($video, $options, $retry) {
            $video->refresh();
            if (! $video->file_path) {
                throw new RuntimeException('The original video is no longer available.');
            }
            if ($retry && $video->processing_status !== VideoProcessingStatus::Failed) {
                throw new RuntimeException('Only failed videos can be retried.');
            }
            $previousAttempt = $video->processing_attempt;
            $attempt = (string) Str::uuid();
            $options = $retry ? ($video->processing_options ?? $options) : $options;
            $video->update([
                'format' => $options['hls'] || $video->hls_playlist_path ? VideoFormat::Hls : VideoFormat::Original,
                'processing_status' => VideoProcessingStatus::Queued,
                'processing_attempt' => $attempt,
                'processing_options' => $options,
                'processing_error' => null,
            ]);
            if ($previousAttempt && ! str_contains($video->hls_playlist_path ?? '', '/'.$previousAttempt.'/')) {
                $this->cleanDirectory(Storage::disk(App::contentDisk()), 'video-streams/'.$video->id.'/'.$previousAttempt);
            }

            return $attempt;
        });
    }

    public function dispatch(Video $video, string $attempt): void
    {
        $id = $video->id;
        DB::afterCommit(function () use ($id, $attempt) {
            try {
                Bus::dispatch(new ProcessVideo($id, $attempt));
            } catch (Throwable $exception) {
                $this->fail($id, $attempt, $exception);
                throw $exception;
            }
        });
    }

    public function process(int $id, string $attempt, bool $markFailed = true): void
    {
        $lock = Cache::lock(static::lockKey($id), (int) config('video.timeout') + 60);
        if (! $lock->get()) {
            throw new LockTimeoutException('Video is already being processed.');
        }

        $root = rtrim(config('video.work_directory'), '/').'/'.$id.'-'.$attempt;
        $prefix = 'video-streams/'.$id.'/'.$attempt;
        $disk = Storage::disk(App::contentDisk());
        $activated = false;
        $posterPath = 'video-posters/'.$id.'/'.$attempt.'.jpg';

        try {
            $video = Video::find($id);
            if (! $video || $video->processing_attempt !== $attempt || $video->isReady()) {
                return;
            }
            if (! $video->file_path || ! $video->processing_options) {
                throw new RuntimeException('The source or processing settings are missing.');
            }
            $video->update(['processing_status' => VideoProcessingStatus::Processing, 'processing_error' => null]);

            File::ensureDirectoryExists($root);
            $work = Storage::build(['driver' => 'local', 'root' => $root, 'throw' => true]);
            $source = 'source.'.pathinfo($video->file_path, PATHINFO_EXTENSION);
            $this->copy($disk, $video->file_path, $work, $source);
            $result = $this->encoder->encode($work, $source, $video->processing_options);
            $this->copy($work, $result['poster'], $disk, $posterPath, ['ContentType' => 'image/jpeg']);
            $playlist = null;
            if ($result['playlist']) {
                $files = $work->allFiles('output');
                if (! in_array($result['playlist'], $files, true)) {
                    throw new RuntimeException('The HLS master playlist is missing.');
                }
                foreach ($files as $path) {
                    $this->copy($work, $path, $disk, $prefix.'/'.substr($path, strlen('output/')), [
                        'ContentType' => str_ends_with($path, '.m3u8') ? 'application/vnd.apple.mpegurl' : 'video/mp2t',
                    ]);
                }
                $playlist = $prefix.'/master.m3u8';
            }

            $previousPoster = $video->poster_image_file_path;
            $previousPlaylist = $video->hls_playlist_path;
            $activated = DB::transaction(function () use ($id, $attempt, $posterPath, $playlist, $result) {
                $current = Video::query()->lockForUpdate()->find($id);
                if (! $current || $current->processing_attempt !== $attempt) {
                    return false;
                }
                $current->update([
                    'poster_image_file_path' => $posterPath,
                    'hls_playlist_path' => $playlist ?: $current->hls_playlist_path,
                    'duration_seconds' => $result['duration'],
                    'processing_status' => VideoProcessingStatus::Ready,
                    'processing_error' => null,
                ]);

                return true;
            });
            if ($activated) {
                if ($previousPoster && $previousPoster !== $posterPath) {
                    $this->cleanFile($disk, $previousPoster);
                }
                if ($previousPlaylist && $playlist && $previousPlaylist !== $playlist) {
                    $this->cleanDirectory($disk, dirname($previousPlaylist));
                }
                if ($playlist && ! $video->processing_options['keep_original'] && $this->cleanFile($disk, $video->file_path)) {
                    Video::query()->whereKey($id)->where('processing_attempt', $attempt)->update(['file_path' => null]);
                }
            }
        } catch (Throwable $exception) {
            if ($markFailed) {
                $this->fail($id, $attempt, $exception);
            }
            throw $exception;
        } finally {
            if (! $activated && ! Video::query()->whereKey($id)->where('hls_playlist_path', $prefix.'/master.m3u8')->exists()) {
                $this->cleanDirectory($disk, $prefix);
            }
            if (! $activated && ! Video::query()->whereKey($id)->where('poster_image_file_path', $posterPath)->exists()) {
                $this->cleanFile($disk, $posterPath);
            }
            File::deleteDirectory($root);
            $lock->release();
        }
    }

    public function fail(int $id, string $attempt, Throwable $exception): void
    {
        if ($exception instanceof LockTimeoutException) {
            return;
        }
        Video::query()->whereKey($id)->where('processing_attempt', $attempt)
            ->where('processing_status', '!=', VideoProcessingStatus::Ready->value)
            ->update(['processing_status' => VideoProcessingStatus::Failed, 'processing_error' => Str::limit($exception->getMessage(), 4000)]);
    }

    /** @param array<string, mixed> $options */
    public function copy(FilesystemAdapter $from, string $source, FilesystemAdapter $to, string $destination, array $options = []): void
    {
        $stream = $from->readStream($source);
        if (! is_resource($stream)) {
            throw new RuntimeException('Unable to read video file: '.$source);
        }
        try {
            if (! $to->writeStream($destination, $stream, $options)) {
                throw new RuntimeException('Unable to write video file: '.$destination);
            }
        } finally {
            fclose($stream);
        }
    }

    protected function cleanFile(FilesystemAdapter $disk, string $path): bool
    {
        try {
            if (! $disk->delete($path)) {
                throw new RuntimeException('Unable to remove video file: '.$path);
            }

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    protected function cleanDirectory(FilesystemAdapter $disk, string $path): void
    {
        try {
            if (! $disk->deleteDirectory($path)) {
                throw new RuntimeException('Unable to remove video directory: '.$path);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
