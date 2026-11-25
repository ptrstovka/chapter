<?php

namespace App\Http\Controllers\Studio;

use App\Models\Course;
use App\Support\CourseDrafts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DiscardCourseDraftController
{
    public function __invoke(Request $request, Course $course, CourseDrafts $drafts): RedirectResponse
    {
        DB::transaction(function () use ($request, $course, $drafts) {
            $current = Course::query()->lockForUpdate()->findOrFail($course->id);
            Gate::authorize('update', $current);
            abort_unless($current->hasPublication() && $current->canBeUpdated(), 409);
            $drafts->verifyRevision($request, $current);
            if ($current->draft) {
                $drafts->remove($current);
            } else {
                $current->increment('studio_revision');
            }
        });

        return to_route('studio.courses.show', $course);
    }
}
