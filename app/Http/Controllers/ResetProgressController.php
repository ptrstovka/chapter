<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ResetProgressController
{
    public function __invoke(Course $course)
    {
        Gate::authorize('study', $course);

        $user = Auth::user();

        DB::transaction(function () use ($user, $course) {
            $enrollment = $user->findEnrollmentFor($course);

            $user
                ->completedLessons()
                ->whereRelation('lesson.course', 'id', $course->id)
                ->delete();

            $enrollment->update([
                'started_at' => now(),
                'completed_at' => null,
                'progress' => 0,
            ]);
        });

        return back();
    }
}
