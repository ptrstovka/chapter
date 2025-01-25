<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property \App\Models\User $user
 * @property \App\Models\Lesson $lesson
 */
class CompletedLesson extends Model
{
    protected $guarded = false;

    protected static function booted(): void
    {
        // Calculate completion progress
        $refreshCompletionProgress = function (CompletedLesson $completion) {
            $course = $completion->lesson->course;

            if ($enrollment = $completion->user->findEnrollmentFor($course)) {
                $completedLessons = $completion->user
                    ->completedLessons()
                    ->whereRelation('lesson.course', 'courses.id', $course->id)
                    ->get();

                $allLessonsCount = $course->lessons()->count();
                $completedCount = $completedLessons->count();

                if ($allLessonsCount == $completedCount) {
                    $enrollment->update([
                        'completed_at' => now(),
                        'progress' => 100,
                    ]);
                } else {
                    $progress = 0;

                    if ($completedCount > 0 && $course->duration_seconds && $course->duration_seconds > 0) {
                        $completedTime = $completedLessons
                            ->load('lesson.video')
                            ->map(fn (CompletedLesson $completion) => $completion->lesson->video?->duration_seconds ?: 0)
                            ->sum();

                        $progress = (int) ceil($completedTime / $course->duration_seconds * 100);
                    }

                    $enrollment->update([
                        'completed_at' => null,
                        'progress' => min($progress, 99),
                    ]);
                }
            }
        };

        static::created($refreshCompletionProgress);
        static::deleted($refreshCompletionProgress);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
