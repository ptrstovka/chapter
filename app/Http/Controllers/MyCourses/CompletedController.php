<?php

namespace App\Http\Controllers\MyCourses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompletedController extends Controller
{
    public function index() {
        return Inertia::render('MyCourses/CompletedList');
    }
}
