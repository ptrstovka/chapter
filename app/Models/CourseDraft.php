<?php

namespace App\Models;

use App\Enums\TextContentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class CourseDraft extends Model
{
    protected $guarded = [];

    protected $attributes = ['has_changes' => false];

    protected function casts(): array
    {
        return ['description_type' => TextContentType::class, 'has_changes' => 'boolean'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function trailer(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(CourseDraftChapter::class, 'course_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseDraftLesson::class, 'course_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(CourseDraftResource::class, 'course_id');
    }

    public function getCoverImageUrl(): ?string
    {
        return $this->cover_image_file_path
            ? Storage::disk(App::contentDisk())->url($this->cover_image_file_path)
            : null;
    }
}
