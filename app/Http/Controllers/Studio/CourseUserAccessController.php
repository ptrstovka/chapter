<?php

namespace App\Http\Controllers\Studio;

use App\Http\Requests\Studio\GrantCourseUserAccessRequest;
use App\Http\Requests\Studio\RevokeCourseUserAccessRequest;
use App\Models\Course;
use App\Models\CourseUserAccess;
use Illuminate\Support\Facades\DB;

class CourseUserAccessController
{
    public function store(GrantCourseUserAccessRequest $request, Course $course)
    {
        DB::transaction(function () use ($request, $course) {
            foreach ($request->userIds() as $userId) {
                CourseUserAccess::query()->firstOrCreate([
                    'course_id' => $course->id,
                    'user_id' => $userId,
                ], [
                    'granted_by_id' => $request->user()->id,
                ]);
            }
        });

        return back();
    }

    public function destroy(RevokeCourseUserAccessRequest $request, Course $course)
    {
        CourseUserAccess::query()
            ->whereBelongsTo($course)
            ->whereIn('user_id', $request->userIds())
            ->delete();

        return back();
    }
}
