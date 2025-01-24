<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $title
 * @property int $position
 * @property \App\Models\Course $course
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Lesson> $lessons
 */
class Chapter extends Model
{
    use HasUuid;

    protected $guarded = false;

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }
}
