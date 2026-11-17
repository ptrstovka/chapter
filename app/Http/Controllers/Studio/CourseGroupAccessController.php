<?php

namespace App\Http\Controllers\Studio;

use App\Http\Requests\Studio\GrantCourseGroupAccessRequest;
use App\Http\Requests\Studio\RevokeCourseGroupAccessRequest;
use App\Models\Course;
use App\Models\CourseGroupAccess;
use Illuminate\Support\Facades\DB;

class CourseGroupAccessController
{
    public function store(GrantCourseGroupAccessRequest $request, Course $course)
    {
        DB::transaction(function () use ($request, $course) {
            foreach ($request->groupIds() as $accessGroupId) {
                CourseGroupAccess::query()->firstOrCreate([
                    'course_id' => $course->id,
                    'access_group_id' => $accessGroupId,
                ], [
                    'granted_by_id' => $request->user()->id,
                ]);
            }
        });

        return back();
    }

    public function destroy(RevokeCourseGroupAccessRequest $request, Course $course)
    {
        CourseGroupAccess::query()
            ->whereBelongsTo($course)
            ->whereIn('access_group_id', $request->groupIds())
            ->delete();

        return back();
    }
}
