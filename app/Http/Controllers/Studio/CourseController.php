<?php


namespace App\Http\Controllers\Studio;


use App\View\Layouts\StudioLayout;
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

    public function show()
    {
        return Inertia::render('Studio/CourseDetail', StudioLayout::make([
            //
        ]));
    }
}
