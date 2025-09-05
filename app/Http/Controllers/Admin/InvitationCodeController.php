<?php


namespace App\Http\Controllers\Admin;


use App\View\Layouts\AdminLayout;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;

class InvitationCodeController
{
    public function index()
    {
        return Inertia::render('Admin/InvitationCodesPage', AdminLayout::make([
            //
        ])->breadcrumb(BreadcrumbItem::make(__('Invitation Codes'))));
    }
}
