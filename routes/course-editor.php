<?php

use App\Http\Controllers\Studio;
use Illuminate\Support\Facades\Route;

Route::prefix('/courses/{course:uuid}')->group(function () {
    Route::get('/', [Studio\CourseController::class, 'show'])->name('courses.show');
    Route::patch('/', [Studio\CourseController::class, 'update'])->name('courses.update');
    Route::delete('/', [Studio\CourseController::class, 'destroy'])->name('courses.destroy');
    Route::get('/content', Studio\CourseContentController::class)->name('courses.content');
    Route::prefix('/preview/{version}')
        ->name('courses.preview.')
        ->middleware('cache.headers:private;no_store')
        ->group(function () {
            Route::get('/', [Studio\CoursePreviewController::class, 'show'])->name('show');
            Route::get('/lessons/{lessonUuid}', [Studio\CoursePreviewController::class, 'lesson'])
                ->whereUuid('lessonUuid')->name('lesson');
            Route::get('/lessons/{lessonUuid}/resources/{resourceUuid}', [Studio\CoursePreviewController::class, 'resource'])
                ->whereUuid(['lessonUuid', 'resourceUuid'])->name('resource');
        });
    Route::get('/access', [Studio\CourseAccessController::class, 'show'])->name('courses.access');
    Route::patch('/access', [Studio\CourseAccessController::class, 'update'])->name('courses.access.update');
    Route::post('/access/users', [Studio\CourseUserAccessController::class, 'store'])->name('courses.access.users.store');
    Route::delete('/access/users', [Studio\CourseUserAccessController::class, 'destroy'])->name('courses.access.users.destroy');
    Route::post('/access/groups', [Studio\CourseGroupAccessController::class, 'store'])->name('courses.access.groups.store');
    Route::delete('/access/groups', [Studio\CourseGroupAccessController::class, 'destroy'])->name('courses.access.groups.destroy');
    Route::post('/access/requests/{courseAccessRequest}/approve', [Studio\CourseAccessRequestController::class, 'approve'])->name('courses.access.requests.approve');
    Route::post('/access/requests/{courseAccessRequest}/deny', [Studio\CourseAccessRequestController::class, 'deny'])->name('courses.access.requests.deny');
    Route::post('/unpublish', Studio\UnpublishCourseController::class)->name('courses.unpublish');
    Route::post('/publish', Studio\PublishCourseController::class)->name('courses.publish');
    Route::delete('/draft', Studio\DiscardCourseDraftController::class)->name('courses.draft.destroy');
    Route::post('/chapters', [Studio\ChapterController::class, 'store'])->name('course.chapters.store');
    Route::post('/sort-chapters', Studio\SortChaptersController::class)->name('course.chapters.sort');
    Route::post('/move-lesson', Studio\MoveLessonController::class)->name('course.lessons.move');
    Route::get('/chapters/{chapter:uuid}', [Studio\ChapterController::class, 'show'])->name('course.chapters.show');
    Route::patch('/chapters/{chapter:uuid}', [Studio\ChapterController::class, 'update'])->name('course.chapters.update');
    Route::post('/chapters/{chapter:uuid}/lessons', [Studio\LessonController::class, 'store'])->name('course.lessons.store');
    Route::delete('/chapters/{chapter:uuid}', [Studio\ChapterController::class, 'destroy'])->name('course.chapters.destroy');
    Route::get('/lessons/{lesson:uuid}', [Studio\LessonController::class, 'show'])->name('course.lessons.show');
    Route::patch('/chapters/{chapter:uuid}/lessons/{lesson:uuid}', [Studio\LessonController::class, 'update'])->name('course.lessons.update');
    Route::delete('/chapters/{chapter:uuid}/lessons/{lesson:uuid}', [Studio\LessonController::class, 'destroy'])->name('course.lessons.destroy');

    Route::delete('/video-uploads/{uploadId}', [Studio\VideoUploadController::class, 'destroy'])->name('video-uploads.destroy');
    Route::post('/video-uploads', [Studio\VideoUploadController::class, 'store'])->name('video-uploads.store');
    Route::get('/videos', [Studio\VideoController::class, 'index'])->name('videos');
    Route::delete('/videos/{video}', [Studio\VideoController::class, 'destroy'])->name('videos.destroy');
    Route::post('/videos/{video}/retry', Studio\VideoRetryController::class)->name('videos.retry');
});
