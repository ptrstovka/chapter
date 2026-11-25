<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CourseDraftResource extends Resource
{
    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseDraft::class, 'course_id');
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(CourseDraftLesson::class, 'course_draft_lesson_resource', 'resource_id', 'lesson_id');
    }
}
