<?php


namespace App\Http\Controllers;


use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EnrollCourseController
{
    public function __invoke(Course $course)
    {
        Gate::authorize('enroll', $course);

        $user = Auth::user();

        $enrollment = $user->findEnrollmentFor($course);

        abort_unless(is_null($enrollment), 400, "You already enrolled this course.");

        $enrollment = new CourseEnrollment([
            'progress' => 0,
            'started_at' => now(),
        ]);
        $enrollment->user()->associate($user);
        $enrollment->course()->associate($course);
        $enrollment->save();

        return to_route('courses.begin', $course->slug);
    }
}
