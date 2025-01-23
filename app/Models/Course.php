<?php

namespace App\Models;

use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $title
 * @property \App\Enums\CourseStatus $status
 * @property string|null $description
 * @property int|null $duration_seconds
 * @property string|null $cover_image_file_path
 * @property \App\Models\Author $author
 * @property \App\Models\Video|null $trailer
 * @property \App\Models\Category $category
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Chapter> $chapters
 * @property \Illuminate\Support\Collection<int, \App\Models\Resource> $resources
 */
class Course extends Model
{
    protected $guarded = false;

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function trailer(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
