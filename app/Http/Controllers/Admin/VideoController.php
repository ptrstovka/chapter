<?php

namespace App\Http\Controllers\Admin;

use App\Support\VideoStatistics;
use App\Table\VideoTable;
use App\View\Layouts\AdminLayout;
use Inertia\Inertia;
use Inertia\Response;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;

class VideoController
{
    public function index(VideoStatistics $statistics): Response
    {
        return Inertia::render('Admin/VideoListPage', AdminLayout::make([
            'videos' => new VideoTable,
            'statistics' => fn () => $statistics->get(),
        ])->breadcrumb(BreadcrumbItem::make(__('Videos'))));
    }
}
