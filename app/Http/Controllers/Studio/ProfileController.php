<?php


namespace App\Http\Controllers\Studio;


use App\View\Layouts\StudioLayout;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;

class ProfileController
{
    public function show()
    {
        return Inertia::render('Studio/Profile', StudioLayout::make([
            //
        ])->breadcrumb(BreadcrumbItem::make(__('Profile'))));
    }
}
