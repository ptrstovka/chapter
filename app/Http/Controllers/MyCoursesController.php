<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class MyCoursesController extends Controller
{
    public function index() {
        return Inertia::render('MyCourses/MyCourses');
    }
}
