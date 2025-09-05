<?php


namespace App\Http\Controllers\Admin;


use App\View\Layouts\AdminLayout;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;

class UserController
{
    public function index()
    {
        return Inertia::render('Admin/UsersPage', AdminLayout::make([

        ])->breadcrumb(BreadcrumbItem::make(__('Users'))));
    }
}
