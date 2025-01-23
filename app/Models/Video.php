<?php

namespace App\Models;

use App\Jobs\CalculateVideoDuration;
use App\Jobs\ExtractVideoPoster;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $file_path
 * @property string|null $poster_image_file_path
 * @property int|null $duration_seconds
 */
class Video extends Model
{
    protected $guarded = false;

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
}
