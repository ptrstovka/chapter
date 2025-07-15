<?php

namespace App\Http\Controllers\MyCourses;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\View\Models\Paginator;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use App\View\Models\CourseCard;

class InProgressController extends Controller
{
    public function __invoke() {

        $user = Auth::user();

        $inProgress = Course::query()
            ->with(['author'])
            ->select('courses.*')
            ->withExists([
                'favoritedBy' => fn (Builder $builder) => $builder->where('id', $user->id),
            ])
            ->join('course_enrollments', function (JoinClause $join) use ($user) {
                $join->on('course_enrollments.course_id', 'courses.id')
                    ->where('course_enrollments.user_id', $user->id);
            })
            ->whereNull('course_enrollments.completed_at')
            ->where('status', CourseStatus::Published)
            ->latest('course_enrollments.updated_at')
            ->paginate(16);

            $enrollments = collect();

            if ($inProgress->collect()->isNotEmpty()) {
                $enrollments = $user
                    ->enrolledCourses()
                    ->whereIn('course_id', $inProgress->collect()->pluck('id'))
                    ->get()
                    ->keyBy(fn (CourseEnrollment $enrollment) => $enrollment->course_id);
            }
        

            return Inertia::render('MyCourses/InProgressList', [
                'inProgress' => Paginator::make(
                    $inProgress->through(fn (Course $course) => new CourseCard($course, $enrollments->get($course->id)))
                ),
            ]);
    }
}
