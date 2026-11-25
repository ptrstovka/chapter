<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CompletedLessonController
{
    public function store(Lesson $lesson): RedirectResponse
    {
        return $this->change($lesson, true);
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        return $this->change($lesson, false);
    }

    private function change(Lesson $lesson, bool $completed): RedirectResponse
    {
        DB::transaction(function () use ($lesson, $completed) {
            $course = Course::query()->lockForUpdate()->findOrFail($lesson->course_id);
            Gate::authorize('study', $course);

            $current = $course->lessons()->findOrFail($lesson->id);
            $user = Auth::user();
            $completion = $user->findLessonCompletionFor($current);
            if ($completed && ! $completion) {
                $current->markCompletedFor($user);
            } elseif (! $completed) {
                $completion?->delete();
            }
        });

        return back();
    }
}
