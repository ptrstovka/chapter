<?php

namespace App\Http\Controllers\MyCourses;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\View\Models\CourseCard;
use Auth;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;
use App\View\Models\Paginator;

class CompletedController extends Controller
{
    public function index(Request $request) 
    {
        $user = Auth::user();

        $completed = Course::query()
            ->with(['author'])
            ->withExists([
                'favoritedBy' => fn (Builder $builder) => $builder->where('id', $user->id),
            ])
            ->join('course_enrollments', function (JoinClause $join) use ($user) {
                $join->on('course_enrollments.course_id', 'courses.id')
                    ->where('course_enrollments.user_id', $user->id)
                    ->whereNotNull('course_enrollments.completed_at');
            })
            ->where('status', CourseStatus::Published)
            ->latest('courses.updated_at')
            ->paginate(16);

        $enrollments = collect();

        if ($completed->isNotEmpty()) {
            $enrollments = $user
                ->enrolledCourses()
                ->whereIn('course_id', $completed->pluck('id'))
                ->get()
                ->keyBy('course_id');
        }
    
        return Inertia::render('MyCourses/CompletedList', [
            'completed' => Paginator::make(
                $completed->through(fn (Course $course) => new CourseCard($course, $enrollments->get($course->id)))
            ),
        ]);
    }
}
