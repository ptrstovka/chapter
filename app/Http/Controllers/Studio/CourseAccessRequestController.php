<?php

namespace App\Http\Controllers\Studio;

use App\Enums\CourseAccessRequestStatus;
use App\Models\Course;
use App\Models\CourseAccessRequest;
use App\Models\CourseUserAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CourseAccessRequestController
{
    public function approve(Course $course, CourseAccessRequest $courseAccessRequest): RedirectResponse
    {
        Gate::authorize('update', $course);

        DB::transaction(function () use ($course, $courseAccessRequest) {
            $this->resolvePendingRequest($course, $courseAccessRequest, CourseAccessRequestStatus::Approved);

            CourseUserAccess::query()->firstOrCreate([
                'course_id' => $course->id,
                'user_id' => $courseAccessRequest->user_id,
            ], [
                'granted_by_id' => auth()->id(),
            ]);
        });

        return back();
    }

    public function deny(Course $course, CourseAccessRequest $courseAccessRequest): RedirectResponse
    {
        Gate::authorize('update', $course);

        $this->resolvePendingRequest($course, $courseAccessRequest, CourseAccessRequestStatus::Denied);

        return back();
    }

    protected function resolvePendingRequest(Course $course, CourseAccessRequest $courseAccessRequest, CourseAccessRequestStatus $status): void
    {
        $resolved = CourseAccessRequest::query()
            ->whereKey($courseAccessRequest->id)
            ->whereBelongsTo($course)
            ->where('status', CourseAccessRequestStatus::Pending)
            ->update([
                'status' => $status,
                'resolved_by_id' => auth()->id(),
                'resolved_at' => now(),
            ]);

        abort_unless($resolved === 1, 404);
    }
}
