<?php

namespace App\Http\Controllers\Studio;

use App\View\Layouts\StudioLayout;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;

class OverviewController
{
    public function __invoke()
    {
        return Inertia::render('Studio/Overview', StudioLayout::make([
            //
        ])->breadcrumb(BreadcrumbItem::make(__('Overview'))));
    }
}
