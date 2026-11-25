<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class NextLessonController
{
    public function __invoke(Request $request, Course $course): RedirectResponse
    {
        return DB::transaction(function () use ($request, $course) {
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);
            Gate::authorize('study', $course);

            $request->validate([
                'from' => ['required', 'uuid', Rule::exists(Lesson::class, 'uuid')->where('course_id', $course->id)],
            ]);

            $lesson = $course->lessons()->where('uuid', $request->input('from'))->firstOrFail();
            $user = Auth::user();

            if (! $user->findLessonCompletionFor($lesson)) {
                $lesson->markCompletedFor($user);
            }

            $nextLesson = $course->lessons()
                ->where('position_within_course', '>', $lesson->position_within_course)
                ->orderBy('position_within_course')
                ->first();

            return $nextLesson ? to_route('lessons.show', [$course->slug, $nextLesson->slug_id]) : back();
        });
    }
}
