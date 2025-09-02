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
use App\Http\Controllers\Studio;
use App\Http\Controllers\TemporaryUploadController;
use App\Http\Controllers\TiptapImageController;
use App\Http\Middleware\StudioMiddleware;
use Illuminate\Support\Facades\Route;

// Route::inertia('/tiptap', 'TiptapPage');

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

    Route::get('/my-courses', InProgressController::class)->name('mycourses.inprogress');
    Route::get('/my-courses/favorites', FavoriteController::class)->name('mycourses.favorite');
    Route::get('/my-courses/completed', CompletedController::class)->name('mycourses.completed');

    Route::prefix('studio')->middleware(StudioMiddleware::class)->group(function () {
        Route::get('/', Studio\StudioController::class)->name('studio');

        Route::name('studio.')->group(function () {
            Route::get('/overview', Studio\OverviewController::class)->name('overview');

            Route::get('/courses', [Studio\CourseController::class, 'index'])->name('courses');
            Route::post('/courses', [Studio\CourseController::class, 'store'])->name('courses.store');
            Route::get('/courses/{course:uuid}', [Studio\CourseController::class, 'show'])->name('courses.show');
            Route::patch('/courses/{course:uuid}', [Studio\CourseController::class, 'update'])->name('courses.update');
            Route::delete('/courses/{course:uuid}', [Studio\CourseController::class, 'destroy'])->name('courses.destroy');
            Route::get('/courses/{course:uuid}/content', Studio\CourseContentController::class)->name('courses.content');
            Route::post('/courses/{course:uuid}/unpublish', Studio\UnpublishCourseController::class)->name('courses.unpublish');
            Route::post('/courses/{course:uuid}/publish', Studio\PublishCourseController::class)->name('courses.publish');
            Route::post('/courses/{course:uuid}/chapters', [Studio\ChapterController::class, 'store'])->name('course.chapters.store');
            Route::post('/courses/{course:uuid}/sort-chapters', Studio\SortChaptersController::class)->name('course.chapters.sort');
            Route::post('/courses/{course:uuid}/move-lesson', Studio\MoveLessonController::class)->name('course.lessons.move');
            Route::get('/courses/{course:uuid}/chapters/{chapter:uuid}', [Studio\ChapterController::class, 'show'])->name('course.chapters.show');
            Route::patch('/courses/{course:uuid}/chapters/{chapter:uuid}', [Studio\ChapterController::class, 'update'])->name('course.chapters.update');
            Route::post('/courses/{course:uuid}/chapters/{chapter:uuid}/lessons', [Studio\LessonController::class, 'store'])->name('course.lessons.store');
            Route::delete('/courses/{course:uuid}/chapters/{chapter:uuid}', [Studio\ChapterController::class, 'destroy'])->name('course.chapters.destroy');
            Route::get('/courses/{course:uuid}/lessons/{lesson:uuid}', [Studio\LessonController::class, 'show'])->name('course.lessons.show');
            Route::patch('/courses/{course:uuid}/chapters/{chapter:uuid}/lessons/{lesson:uuid}', [Studio\LessonController::class, 'update'])->name('course.lessons.update');
            Route::delete('/courses/{course:uuid}/chapters/{chapter:uuid}/lessons/{lesson:uuid}', [Studio\LessonController::class, 'destroy'])->name('course.lessons.destroy');

            Route::get('/profile', [Studio\ProfileController::class, 'show'])->name('profile');
        });
    });

    Route::post('/files', [TemporaryUploadController::class, 'store'])->name('files.store');

    Route::post('/tiptap/images', [TiptapImageController::class, 'store'])->name('tiptap.images.store');
});

require __DIR__.'/auth.php';
