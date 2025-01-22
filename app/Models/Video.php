<?php

namespace App\Models;

use App\Enums\VideoStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $file_path
 * @property string|null $thumbnail_file_path
 * @property \App\Enums\VideoStatus $status
 * @property int|null $duration_seconds
 */
class Video extends Model
{
    protected $guarded = false;

    protected function casts(): array
    {
        return [
            'status' => VideoStatus::class,
        ];
    }
}
