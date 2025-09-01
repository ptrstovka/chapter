<?php

namespace App\Http\Controllers\MyCourses;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\View\Models\CourseCard;
use App\View\Models\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InProgressController extends Controller
{
    public function __invoke()
    {

        $user = Auth::user();

        $inProgress = Course::query()
            ->with([
                'author',
                'enrollments' => fn (HasMany $query) => $query->where('user_id', $user->id),
            ])
            ->select('courses.*')
            ->withExists([
                'favoritedBy' => fn (Builder $builder) => $builder->where('id', $user->id),
            ])
            ->join('course_enrollments', function (JoinClause $join) use ($user) {
                $join->on('course_enrollments.course_id', 'courses.id')
                    ->where('course_enrollments.user_id', $user->id)
                    ->whereNull('course_enrollments.completed_at');
            })
            ->where('status', CourseStatus::Published)
            ->latest('course_enrollments.updated_at')
            ->paginate(16);

        return Inertia::render('MyCourses/InProgressList', [
            'inProgress' => Paginator::make(
                $inProgress->through(fn (Course $course) => new CourseCard($course, $course->enrollments->first()))
            ),
        ]);
    }
}
