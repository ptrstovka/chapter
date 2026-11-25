<?php

namespace App\Http\Controllers\Studio;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Support\CourseDrafts;
use App\Support\CoursePublication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PublishCourseController
{
    public function __invoke(Request $request, Course $course, CourseDrafts $drafts, CoursePublication $publication): RedirectResponse
    {
        DB::transaction(function () use ($request, $course, $drafts, $publication) {
            $current = Course::query()->lockForUpdate()->findOrFail($course->id);
            Gate::authorize('publish', $current);

            if ($current->hasPublication()) {
                $drafts->verifyRevision($request, $current);
            }

            if ($current->status === CourseStatus::Publishing) {
                return;
            }

            if ($problem = $publication->problem($current)) {
                throw ValidationException::withMessages(['publication' => $problem]);
            }

            $current->publish(allowChanges: true);
        });

        return back();
    }
}
