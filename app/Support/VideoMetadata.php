<?php

namespace App\Support;

use App\Models\Video;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemOperator;
use RuntimeException;

class VideoMetadata
{
    public function refresh(Video $video): bool
    {
        return Cache::lock(VideoProcessing::lockKey($video->id), 60)->get(function () use ($video) {
            $current = $video->fresh();
            if (! $current) {
                return false;
            }

            $filesystem = Storage::disk(App::contentDisk())->getDriver();
            $originalSize = $current->file_path ? $filesystem->fileSize($current->file_path) : null;
            $hlsSize = $current->hls_playlist_path ? $this->hlsSize($filesystem, $current->hls_playlist_path) : null;

            return Video::query()->whereKey($current->id)
                ->where('file_path', $current->file_path)
                ->where('hls_playlist_path', $current->hls_playlist_path)
                ->where('processing_attempt', $current->processing_attempt)
                ->update(['original_size_bytes' => $originalSize, 'hls_size_bytes' => $hlsSize]) > 0;
        });
    }

    protected function hlsSize(FilesystemOperator $filesystem, string $playlist): int
    {
        $directory = dirname($playlist);
        if (in_array($directory, ['.', '/', ''], true)) {
            throw new RuntimeException('The HLS playlist must be stored in its own directory.');
        }

        $size = 0;
        $foundPlaylist = false;
        foreach ($filesystem->listContents($directory, true) as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $foundPlaylist = $foundPlaylist || $file->path() === $playlist;
            $size += $file->fileSize() ?? $filesystem->fileSize($file->path());
        }

        if (! $foundPlaylist) {
            throw new RuntimeException('The HLS master playlist is missing.');
        }

        return $size;
    }
}
