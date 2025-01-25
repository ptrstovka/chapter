<?php

use App\Http\Controllers\BeginCourseController;
use App\Http\Controllers\CompletedLessonController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollCourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\NextLessonController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses');
    Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{course:uuid}/enroll', EnrollCourseController::class)->name('courses.enroll');
    Route::get('/courses/{course:slug}/begin', BeginCourseController::class)->name('courses.begin');

    Route::post('/courses/{course:slug}/next', NextLessonController::class)->name('lessons.next');
    Route::get('/courses/{course:slug}/{lesson:slugid}', [LessonController::class, 'show'])->name('lessons.show');

    Route::post('/completed-lessons/{lesson:uuid}', [CompletedLessonController::class, 'store'])->name('completed-lessons.store');
    Route::delete('/completed-lessons/{lesson:uuid}', [CompletedLessonController::class, 'destroy'])->name('completed-lessons.destroy');
});

require __DIR__.'/auth.php';
