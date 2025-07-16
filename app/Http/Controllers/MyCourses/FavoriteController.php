<?php

namespace App\Http\Controllers\MyCourses;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\View\Models\CourseCard;
use Auth;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;
use App\View\Models\Paginator;

class FavoriteController extends Controller
{
    public function __invoke() 
    {
        $user = Auth::user();

        $favorites = Course::query()
            ->with(['author'])
            ->withExists([
                'favoritedBy' => fn (Builder $builder) => $builder->where('id', $user->id),
            ])
            ->whereHas('favoritedBy', function(Builder $query) use ($user) {
                $query->where('id', $user->id);
            })
            ->where('status', CourseStatus::Published)
            ->paginate(16);

        $enrollments = collect();

        if ($favorites->isNotEmpty()) {
            $enrollments = $user
                ->enrolledCourses()
                ->whereIn('course_id', $favorites->pluck('id'))
                ->get()
                ->keyBy('course_id');
        }
    
        return Inertia::render('MyCourses/FavoriteList', [
            'favorites' => Paginator::make(
                $favorites->through(fn (Course $course) => new CourseCard($course, $enrollments->get($course->id)))
            ),
        ]);
    }
}