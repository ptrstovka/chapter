<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseDraftChapter extends Chapter
{
    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseDraft::class, 'course_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseDraftLesson::class, 'chapter_id');
    }
}
