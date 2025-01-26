<?php


namespace App\Http\Controllers;


use App\Models\Course;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController
{
    public function __invoke()
    {
        // <!-- TODO: Continue where you left off (pokračovanie rozrobenych kurzov) -->
        // <!-- TODO: Latest courses (naposledy pridane nove kurzy) -->
        // <!-- TODO: Popular courses (kurzy zoradenie podľa najvyššieho počtu enrollmentov) -->
        // <!-- TODO: Discover (random kurzy) -->

        $user = Auth::user();

        // Courses In Progress
        $courses = Course::query()
            ->select('courses.*')
            ->join('course_enrollments', function (JoinClause $join) use ($user) {
                $join->on('course_enrollments.course_id', 'courses.id')
                    ->where('course_enrollments.user_id', $user->id);
            })
            ->whereNull('course_enrollments.completed_at')
            ->latest('course_enrollments.updated_at')
            ->get();

        return Inertia::render('Dashboard/Dashboard', [

        ]);
    }
}
