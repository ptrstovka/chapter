<?php

namespace App\Models;

use App\Adapters\LessonResourceFileListAdapter;
use App\Enums\TextContentType;
use App\Models\Concerns\HasSlugId;
use App\Models\Concerns\HasUuid;
use App\Support\FileList;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property \App\Enums\TextContentType $description_type
 * @property \App\Models\Video|null $video
 * @property int $position
 * @property int $position_within_course
 * @property \App\Models\Chapter $chapter
 * @property \App\Models\Course $course
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Resource> $resources
 *
 * @method static \Database\Factories\LessonFactory factory($count = null, $state = [])
 */
class Lesson extends Model
{
    use HasFactory, HasSlugId, HasUuid;

    protected $guarded = false;

    protected function casts(): array
    {
        return [
            'description_type' => TextContentType::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Lesson $lesson) {
            $video = $lesson->video;
            $lesson->video()->disassociate()->save();
            $video?->delete();

            $lesson->resources->each->delete();
        });
    }

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
     * Create a file list for lesson resources.
     */
    public function resourceFiles(): FileList
    {
        return new FileList(new LessonResourceFileListAdapter($this));
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

    /**
     * Get the lesson fallback title.
     */
    public function getFallbackTitle(): string
    {
        return __('Lesson :value', ['value' => $this->position]);
    }
}
