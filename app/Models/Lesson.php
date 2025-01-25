<?php

namespace App\Models;

use App\Models\Concerns\HasSlugId;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property \App\Models\Video|null $video
 * @property int $position
 * @property \App\Models\Chapter $chapter
 * @property \App\Models\Course $course
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Resource> $resources
 */
class Lesson extends Model
{
    use HasUuid, HasSlugId;

    protected $guarded = false;

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class);
    }

    /**
     * Mark lesson as completed for givn user.
     */
    public function markCompletedFor(User $user): CompletedLesson
    {
        $completion = new CompletedLesson;
        $completion->user()->associate($user);
        $completion->lesson()->associate($this);
        $completion->save();

        return $completion;
    }
}
