<?php


namespace App\Http\Controllers\Admin;


use App\Table\CourseTable;
use App\View\Layouts\AdminLayout;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;

class CourseController
{
    public function index()
    {
        $courses = new CourseTable(author: null);

        return Inertia::render('Admin/CourseList', AdminLayout::make([
            'courses' => $courses,
        ])->breadcrumb(BreadcrumbItem::make(__('Courses'))));
    }
}
