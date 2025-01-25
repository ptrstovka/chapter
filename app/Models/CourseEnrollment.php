<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon $completed_at
 * @property int $progress
 * @property \App\Models\User $user
 * @property \App\Models\Course $course
 */
class CourseEnrollment extends Model
{
    protected $guarded = false;

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Determine whether the course is completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at != null;
    }
}
