<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CompletedLessonController
{
    public function store(Lesson $lesson)
    {
        Gate::authorize('study', $lesson->course);

        $user = Auth::user();

        $completion = $user->findLessonCompletionFor($lesson);

        if ($completion) {
            return back();
        }

        $lesson->markCompletedFor($user);

        return back();
    }

    public function destroy(Lesson $lesson)
    {
        Gate::authorize('study', $lesson->course);

        Auth::user()->findLessonCompletionFor($lesson)?->delete();

        return back();
    }
}
