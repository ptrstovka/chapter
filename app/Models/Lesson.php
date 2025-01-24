<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $title
 * @property string|null $description
 * @property \App\Models\Video|null $video
 * @property int $position
 * @property \App\Models\Chapter $chapter
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Resource> $resources
 */
class Lesson extends Model
{
    use HasUuid;

    protected $guarded = false;

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class);
    }
}
