<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $title
 * @property int $position
 * @property \App\Models\Course $course
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Lesson> $lessons
 *
 * @method static \Database\Factories\ChapterFactory factory($count = null, $state = [])
 */
class Chapter extends Model
{
    use HasUuid, HasFactory;

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
