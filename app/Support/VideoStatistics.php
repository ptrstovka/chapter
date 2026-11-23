<?php

namespace App\Support;

use App\Enums\VideoProcessingStatus;
use App\Enums\VideoUploadStatus;
use App\Models\Video;
use App\Models\VideoUpload;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class VideoStatistics
{
    /**
     * @return array{
     *     total: int, durationSeconds: int, unknownDurationCount: int,
     *     hlsCount: int, originalCount: int, readyCount: int, queuedCount: int, processingCount: int, failedCount: int,
     *     originalBytes: int, hlsBytes: int, knownSizeBytes: int, unknownOriginalSizeCount: int, unknownHlsSizeCount: int,
     *     labels: array{total: string, duration: string, hlsCount: string, hlsPercentage: string, size: string, originals: string, hls: string}
     * }
     */
    public function get(): array
    {
        $videos = Video::query()
            ->select(['duration_seconds', 'file_path', 'original_size_bytes', 'hls_playlist_path', 'hls_size_bytes', 'processing_status'])
            ->addSelect(['uploaded_size_bytes' => VideoUpload::query()
                ->select('size')
                ->whereColumn('video_id', (new Video)->qualifyColumn('id'))
                ->where('status', VideoUploadStatus::Completed)
                ->orderByDesc('id')
                ->limit(1),
            ]);

        $totals = DB::query()->fromSub($videos, 'video_statistics')
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(duration_seconds), 0) as duration_seconds, COUNT(duration_seconds) as known_durations')
            ->selectRaw("COUNT(NULLIF(hls_playlist_path, '')) as hls_count, COUNT(NULLIF(file_path, '')) as original_count")
            ->selectRaw('COUNT(CASE WHEN processing_status = ? THEN 1 END) as ready_count', [VideoProcessingStatus::Ready->value])
            ->selectRaw('COUNT(CASE WHEN processing_status = ? THEN 1 END) as queued_count', [VideoProcessingStatus::Queued->value])
            ->selectRaw('COUNT(CASE WHEN processing_status = ? THEN 1 END) as processing_count', [VideoProcessingStatus::Processing->value])
            ->selectRaw('COUNT(CASE WHEN processing_status = ? THEN 1 END) as failed_count', [VideoProcessingStatus::Failed->value])
            ->selectRaw("COALESCE(SUM(CASE WHEN NULLIF(file_path, '') IS NOT NULL THEN COALESCE(original_size_bytes, uploaded_size_bytes) END), 0) as original_bytes")
            ->selectRaw("COALESCE(SUM(CASE WHEN NULLIF(hls_playlist_path, '') IS NOT NULL THEN hls_size_bytes END), 0) as hls_bytes")
            ->selectRaw("COUNT(CASE WHEN NULLIF(file_path, '') IS NOT NULL THEN COALESCE(original_size_bytes, uploaded_size_bytes) END) as known_original_sizes")
            ->selectRaw("COUNT(CASE WHEN NULLIF(hls_playlist_path, '') IS NOT NULL THEN hls_size_bytes END) as known_hls_sizes")
            ->first();

        $total = (int) $totals->total;
        $duration = (int) $totals->duration_seconds;
        $originalCount = (int) $totals->original_count;
        $hlsCount = (int) $totals->hls_count;
        $originalBytes = (int) $totals->original_bytes;
        $hlsBytes = (int) $totals->hls_bytes;
        $knownOriginalSizes = (int) $totals->known_original_sizes;
        $knownHlsSizes = (int) $totals->known_hls_sizes;
        $locale = App::getLocale();

        return [
            'total' => $total,
            'durationSeconds' => $duration,
            'unknownDurationCount' => $total - (int) $totals->known_durations,
            'hlsCount' => $hlsCount,
            'originalCount' => $originalCount,
            'readyCount' => (int) $totals->ready_count,
            'queuedCount' => (int) $totals->queued_count,
            'processingCount' => (int) $totals->processing_count,
            'failedCount' => (int) $totals->failed_count,
            'originalBytes' => $originalBytes,
            'hlsBytes' => $hlsBytes,
            'knownSizeBytes' => $originalBytes + $hlsBytes,
            'unknownOriginalSizeCount' => $originalCount - $knownOriginalSizes,
            'unknownHlsSizeCount' => $hlsCount - $knownHlsSizes,
            'labels' => [
                'total' => Number::format($total, locale: $locale),
                'duration' => Duration::seconds($duration)->formatWithSeconds(),
                'hlsCount' => Number::format($hlsCount, locale: $locale),
                'hlsPercentage' => Number::percentage($total ? $hlsCount / $total * 100 : 0, maxPrecision: 1, locale: $locale),
                'size' => $this->sizeLabel($originalBytes + $hlsBytes, $originalCount + $hlsCount, $knownOriginalSizes + $knownHlsSizes),
                'originals' => $this->sizeLabel($originalBytes, $originalCount, $knownOriginalSizes),
                'hls' => $this->sizeLabel($hlsBytes, $hlsCount, $knownHlsSizes),
            ],
        ];
    }

    protected function sizeLabel(int $bytes, int $count, int $knownCount): string
    {
        return $count > 0 && $knownCount === 0 ? __('Unknown') : Number::fileSize($bytes);
    }
}
