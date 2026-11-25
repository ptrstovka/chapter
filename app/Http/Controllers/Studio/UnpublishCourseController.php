<?php

namespace App\Http\Controllers\Studio;

use App\Models\Course;
use App\Support\CourseDrafts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UnpublishCourseController
{
    public function __invoke(Request $request, Course $course, CourseDrafts $drafts): RedirectResponse
    {
        DB::transaction(function () use ($request, $course, $drafts) {
            $current = Course::query()->lockForUpdate()->findOrFail($course->id);
            Gate::authorize('unpublish', $current);
            $drafts->verifyRevision($request, $current);
            $current->unpublish();
        });

        return back();
    }
}
