<?php

namespace App\Models;

use App\Enums\VideoUploadStatus;
use App\Support\CourseDrafts;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CourseDraftLesson extends Lesson
{
    protected static function booted(): void
    {
        static::deleting(function (CourseDraftLesson $lesson) {
            VideoUpload::query()->where('draft_lesson_id', $lesson->id)
                ->where('status', VideoUploadStatus::Pending)
                ->update(['status' => VideoUploadStatus::Cancelled]);

            $resources = $lesson->resources;
            $lesson->resources()->detach();
            foreach ($resources as $resource) {
                if ($resource->lessons()->doesntExist()) {
                    $resource->delete();
                }
            }

            app(CourseDrafts::class)->deleteUnusedVideo($lesson->video_id);
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseDraft::class, 'course_id');
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(CourseDraftChapter::class, 'chapter_id');
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(CourseDraftResource::class, 'course_draft_lesson_resource', 'lesson_id', 'resource_id');
    }
}
