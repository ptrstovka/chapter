<?php

namespace App\Models;

use Database\Factories\CourseUserAccessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Course $course
 * @property User $user
 * @property User|null $grantedBy
 *
 * @method static \Database\Factories\CourseUserAccessFactory factory($count = null, $state = [])
 */
class CourseUserAccess extends Model
{
    /** @use HasFactory<CourseUserAccessFactory> */
    use HasFactory;

    protected $table = 'course_user_access';

    protected $guarded = false;

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_id');
    }
}
