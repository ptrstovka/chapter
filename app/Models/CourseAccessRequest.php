<?php

namespace App\Models;

use App\Enums\CourseAccessRequestStatus;
use Carbon\Carbon;
use Database\Factories\CourseAccessRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Course $course
 * @property User $user
 * @property CourseAccessRequestStatus $status
 * @property User|null $resolvedBy
 * @property Carbon|null $resolved_at
 *
 * @method static \Database\Factories\CourseAccessRequestFactory factory($count = null, $state = [])
 */
class CourseAccessRequest extends Model
{
    /** @use HasFactory<CourseAccessRequestFactory> */
    use HasFactory;

    protected $guarded = false;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => CourseAccessRequestStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }
}
