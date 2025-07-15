<?php

use App\Http\Controllers\BeginCourseController;
use App\Http\Controllers\CompletedLessonController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollCourseController;
use App\Http\Controllers\FavoriteCourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MyCourses\CompletedController;
use App\Http\Controllers\MyCourses\FavoriteController;
use App\Http\Controllers\MyCourses\InProgressController;
use App\Http\Controllers\NextLessonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResetProgressController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses');
    Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{course:uuid}/enroll', EnrollCourseController::class)->name('courses.enroll');
    Route::get('/courses/{course:slug}/begin', BeginCourseController::class)->name('courses.begin');
    Route::post('/courses/{course:slug}/reset', ResetProgressController::class)->name('courses.reset-progress');
    Route::post('/courses/{course:slug}/favorite', [FavoriteCourseController::class, 'store'])->name('courses.favorite');
    Route::delete('/courses/{course:slug}/favorite', [FavoriteCourseController::class, 'destroy'])->name('courses.unfavorite');
    Route::post('/courses/{course:slug}/lessons/next', NextLessonController::class)->name('lessons.next');
    Route::get('/courses/{course:slug}/{lesson:slugid}', [LessonController::class, 'show'])->name('lessons.show');

    Route::post('/completed-lessons/{lesson:uuid}', [CompletedLessonController::class, 'store'])->name('completed-lessons.store');
    Route::delete('/completed-lessons/{lesson:uuid}', [CompletedLessonController::class, 'destroy'])->name('completed-lessons.destroy');

    Route::get('/courses/{course:slug}/resources/{resource:uuid}', [ResourceController::class, 'show'])->name('resources.show');

    Route::get('/search', SearchController::class)->name('search');

    Route::get('/my-courses',InProgressController::class)->name('mycourses');
    Route::get('/my-courses/favorites', [FavoriteController::class, 'index'])->name('mycourses.favorite');
    Route::get('/my-courses/completed', [CompletedController::class, 'index'])->name('mycourses.completed');
});

require __DIR__.'/auth.php';
