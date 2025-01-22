<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $title
 * @property string|null $description
 * @property \App\Models\Video|null $video
 * @property int $position
 */
class Lesson extends Model
{
    protected $guarded = false;

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
