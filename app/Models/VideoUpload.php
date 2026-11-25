<?php

namespace App\Models;

use App\Enums\VideoUploadStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoUpload extends Model
{
    use HasFactory, HasUuid, Prunable;

    protected $guarded = false;

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return ['status' => VideoUploadStatus::class, 'expires_at' => 'datetime', 'size' => 'integer', 'editor_revision' => 'integer'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function draftLesson(): BelongsTo
    {
        return $this->belongsTo(CourseDraftLesson::class, 'draft_lesson_id');
    }

    public function temporaryUpload(): BelongsTo
    {
        return $this->belongsTo(TemporaryUpload::class)->withTrashed();
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', VideoUploadStatus::Pending)->where('expires_at', '>', now());
    }

    public function uploadScope(): string
    {
        return $this->lesson_uuid || $this->lesson_id || $this->draft_lesson_id ? 'CourseVideo' : 'CourseTrailerVideo';
    }

    public function lessonUuid(): ?string
    {
        return $this->lesson_uuid ?? $this->draftLesson?->uuid ?? $this->lesson?->uuid;
    }

    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<=', now());
    }

    protected function pruning(): void
    {
        $this->temporaryUpload?->forceDelete();
    }

    /** @return array<string, mixed> */
    public function toUpload(): array
    {
        return [
            'id' => $this->uuid,
            'courseId' => $this->course?->uuid,
            'lessonId' => $this->lessonUuid(),
            'name' => $this->filename,
            'size' => $this->size,
            'status' => $this->status,
            'received' => $this->temporary_upload_id !== null,
            'videoId' => $this->video_id,
            'editorRevision' => $this->editor_revision === null
                ? null
                : ['before' => $this->editor_revision - 1, 'after' => $this->editor_revision],
        ];
    }
}
