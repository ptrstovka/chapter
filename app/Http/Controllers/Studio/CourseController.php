<?php


namespace App\Http\Controllers\Studio;


use App\Enums\CourseStatus;
use App\Models\Course;
use App\View\Layouts\CourseLayout;
use App\View\Layouts\StudioLayout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;

class CourseController
{
    public function index()
    {
        return Inertia::render('Studio/CourseList', StudioLayout::make([
            //
        ])->breadcrumb(BreadcrumbItem::make(__('Courses'))));
    }

    public function store()
    {
        Gate::authorize('create', Course::class);

        $user = Auth::user();

        $course = new Course([
            'status' => CourseStatus::Draft,
        ]);

        $course->author()->associate($user->author);

        $course->save();

        return to_route('studio.courses.show', $course);
    }

    public function show(Course $course)
    {
        Gate::authorize('view', $course);

        return Inertia::render('Studio/CourseDetail', CourseLayout::make($course, [

        ])->breadcrumb(BreadcrumbItem::make(__('General'))));
    }
}
