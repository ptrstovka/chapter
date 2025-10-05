<?php

namespace App\Models;

use App\Jobs\CalculateVideoDuration;
use App\Jobs\ExtractVideoPoster;
use App\Support\Duration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $file_path
 * @property string|null $poster_image_file_path
 * @property int|null $duration_seconds
 */
class Video extends Model
{
    protected $guarded = false;

    protected static function booted(): void
    {
        static::deleted(function (Video $video) {
            $disk = App::contentDisk();

            if (Storage::disk($disk)->exists($video->file_path)) {
                Storage::disk($disk)->delete($video->file_path);
            }

            if ($poster = $video->poster_image_file_path) {
                if (Storage::disk($disk)->exists($poster)) {
                    Storage::disk($disk)->delete($poster);
                }
            }
        });
    }

    /**
     * Retrieve list of jobs which should be executed for this video prior to publishing.
     * All jobs must implement Batchable trait. The order of execution is not guaranteed,
     * therefore jobs should not depend on results of previously run jobs.
     */
    public function getProcessingJobs(): array
    {
        $jobs = [];

        if (! $this->poster_image_file_path) {
            $jobs[] = new ExtractVideoPoster($this);
        }

        if (is_null($this->duration_seconds)) {
            $jobs[] = new CalculateVideoDuration($this);
        }

        return $jobs;
    }

    /**
     * Retrieve URL to the video.
     */
    public function getUrl(): ?string
    {
        return Storage::disk(App::contentDisk())->url($this->file_path);
    }

    /**
     * Retrieve URL to the video poster.
     */
    public function getPosterImageUrl(): ?string
    {
        if ($this->poster_image_file_path) {
            return Storage::disk(App::contentDisk())->url($this->poster_image_file_path);
        }

        return null;
    }

    /**
     * Retrieve total video duration as label.
     */
    public function getDurationLabel(): ?string
    {
        return $this->duration_seconds
            ? Duration::seconds($this->duration_seconds)->format()
            : null;
    }
}
