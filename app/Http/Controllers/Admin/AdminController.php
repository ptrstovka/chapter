<?php


namespace App\Http\Controllers\Admin;


class AdminController
{
    public function __invoke()
    {
        return to_route('admin.courses');
    }
}
