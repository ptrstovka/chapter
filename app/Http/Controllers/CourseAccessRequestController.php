<?php

namespace App\Http\Controllers;

use App\Enums\CourseAccessMode;
use App\Enums\CourseAccessRequestStatus;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CourseAccessRequest;
use Illuminate\Support\Facades\Auth;

class CourseAccessRequestController
{
    public function __invoke(Course $course)
    {
        $user = Auth::user();

        abort_unless(
            $course->status === CourseStatus::Published
            && $course->access_mode === CourseAccessMode::RequestOnly
            && $course->isVisibleToLearner($user),
            404
        );

        abort_if($course->isAccessibleToLearner($user), 400, __('You already have access to this course.'));

        CourseAccessRequest::query()->firstOrCreate([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => CourseAccessRequestStatus::Pending->value,
        ]);

        return back();
    }
}
