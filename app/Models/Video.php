<?php

namespace App\Models;

use App\Enums\VideoFormat;
use App\Enums\VideoProcessingStatus;
use App\Enums\VideoUploadStatus;
use App\Jobs\DeleteVideoFiles;
use App\Support\Duration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * @property string|null $file_path
 * @property string|null $hls_playlist_path
 * @property int|null $hls_size_bytes
 * @property string|null $poster_image_file_path
 * @property int|null $duration_seconds
 * @property VideoFormat $format
 * @property VideoProcessingStatus $processing_status
 * @property string|null $processing_attempt
 * @property array{hls: bool, keep_original: bool, qualities: array<int, int>}|null $processing_options
 */
class Video extends Model
{
    use HasFactory;

    protected $guarded = false;

    protected $attributes = ['format' => 'original', 'processing_status' => 'queued'];

    protected function casts(): array
    {
        return [
            'format' => VideoFormat::class,
            'processing_status' => VideoProcessingStatus::class,
            'processing_options' => 'array',
            'duration_seconds' => 'integer',
            'hls_size_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (Video $video) {
            $cleanup = new DeleteVideoFiles(App::contentDisk(), $video->id, $video->file_path, $video->poster_image_file_path);
            DB::afterCommit(function () use ($cleanup) {
                try {
                    Bus::dispatch($cleanup);
                } catch (Throwable $exception) {
                    report($exception);
                    try {
                        $cleanup->handle();
                    } catch (Throwable $cleanupException) {
                        report($cleanupException);
                    }
                }
            });
        });
    }

    public function isReady(): bool
    {
        return $this->processing_status === VideoProcessingStatus::Ready
            && $this->poster_image_file_path && $this->duration_seconds !== null
            && ($this->format === VideoFormat::Hls ? (bool) $this->hls_playlist_path : (bool) $this->file_path);
    }

    public function canConvertToHls(): bool
    {
        return $this->file_path && ! $this->hls_playlist_path
            && in_array($this->processing_status, [VideoProcessingStatus::Ready, VideoProcessingStatus::Failed], true);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function trailerCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'trailer_id')->withTrashed();
    }

    public function latestCompletedUpload(): HasOne
    {
        return $this->hasOne(VideoUpload::class)->ofMany(
            ['id' => 'max'],
            fn (Builder $query) => $query->where('status', VideoUploadStatus::Completed),
        );
    }

    public function getUrl(): ?string
    {
        $path = $this->hls_playlist_path ?: ($this->format === VideoFormat::Original && $this->isReady() ? $this->file_path : null);

        return $path ? Storage::disk(App::contentDisk())->url($path) : null;
    }

    public function getMimeType(): string
    {
        return $this->hls_playlist_path || $this->format === VideoFormat::Hls
            ? 'application/vnd.apple.mpegurl'
            : (strtolower(pathinfo($this->file_path ?? '', PATHINFO_EXTENSION)) === 'mov' ? 'video/quicktime' : 'video/mp4');
    }

    public function getPosterImageUrl(): ?string
    {
        return $this->poster_image_file_path ? Storage::disk(App::contentDisk())->url($this->poster_image_file_path) : null;
    }

    public function getDurationLabel(): ?string
    {
        return $this->duration_seconds ? Duration::seconds($this->duration_seconds)->format() : null;
    }
}
