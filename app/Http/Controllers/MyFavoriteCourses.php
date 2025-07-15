<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class MyFavoriteCourses extends Controller
{
    public function index() {
        return Inertia::render('MyCourses/MyFavoriteCourses');
    }
} 
