<?php


namespace App\Http\Controllers\Studio;


use App\Models\Course;
use App\View\Layouts\CourseLayout;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;

class CourseContentController
{
    public function __invoke(Course $course)
    {
        return Inertia::render('Studio/CourseContent', CourseLayout::make($course, [

        ])->breadcrumb(BreadcrumbItem::make(__('Content'))));
    }
}
