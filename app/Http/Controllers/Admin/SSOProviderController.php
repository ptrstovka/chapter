<?php


namespace App\Http\Controllers\Admin;


use App\View\Layouts\AdminLayout;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;

class SSOProviderController
{
    public function index()
    {
        return Inertia::render('Admin/SSOProvidersPage', AdminLayout::make([
            //
        ])->breadcrumb(BreadcrumbItem::make(__('SSO Providers'))));
    }
}
