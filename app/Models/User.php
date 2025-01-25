<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $email
 * @property string $name
 * @property \App\Models\Author|null $author
 * @property boolean $is_admin
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\CourseEnrollment> $enrolledCourses
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\CompletedLesson> $completedLessons
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $guarded = false;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function enrolledCourses(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function completedLessons(): HasMany
    {
        return $this->hasMany(CompletedLesson::class);
    }

    /**
     * Retrieve enrollment for given course.
     */
    public function findEnrollmentFor(Course $course): ?CourseEnrollment
    {
        return $this->enrolledCourses()->whereBelongsTo($course)->latest()->first();
    }

    /**
     * Retrieve lesson completion for given course lesson.
     */
    public function findLessonCompletionFor(Lesson $lesson): ?CompletedLesson
    {
        return $this->completedLessons()->whereBelongsTo($lesson)->first();
    }

    /**
     * Determine whether user is enrolled in given course.
     */
    public function isEnrolledIn(Course $course): bool
    {
        return $this->enrolledCourses()->whereBelongsTo($course)->exists();
    }
}
