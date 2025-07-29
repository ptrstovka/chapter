<?php

namespace App\Http\Controllers\MyCourses;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\View\Models\CourseCard;
use Auth;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;
use App\View\Models\Paginator;

class CompletedController extends Controller
{
    public function __invoke() 
    {
        $user = Auth::user();

        $completed = Course::query()
            ->with([
                'author',
                'enrollments' => fn (HasMany $query) => $query->where('user_id', $user->id)
            ])
            ->withExists([
                'favoritedBy' => fn (Builder $builder) => $builder->where('id', $user->id),
            ])
            ->join('course_enrollments', function (JoinClause $join) use ($user) {
                $join->on('course_enrollments.course_id', 'courses.id')
                    ->where('course_enrollments.user_id', $user->id)
                    ->whereNotNull('course_enrollments.completed_at');
            })
            ->where('status', CourseStatus::Published)
            ->orderByDesc('completed_at')
            ->paginate(16);

    
        return Inertia::render('MyCourses/CompletedList', [ 
            'completed' => Paginator::make(
                $completed->through(fn (Course $course) => new CourseCard($course, $course->enrollments->first()))
            )
        ]);
    }
}
