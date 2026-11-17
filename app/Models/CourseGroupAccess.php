<?php

namespace App\Models;

use Database\Factories\CourseGroupAccessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Course $course
 * @property AccessGroup $accessGroup
 * @property User|null $grantedBy
 *
 * @method static \Database\Factories\CourseGroupAccessFactory factory($count = null, $state = [])
 */
class CourseGroupAccess extends Model
{
    /** @use HasFactory<CourseGroupAccessFactory> */
    use HasFactory;

    protected $table = 'course_group_access';

    protected $guarded = false;

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function accessGroup(): BelongsTo
    {
        return $this->belongsTo(AccessGroup::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_id');
    }
}
