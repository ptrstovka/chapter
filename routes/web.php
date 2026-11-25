<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\BeginCourseController;
use App\Http\Controllers\CompletedLessonController;
use App\Http\Controllers\CourseAccessRequestController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollCourseController;
use App\Http\Controllers\FavoriteCourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MyCourses\CompletedController;
use App\Http\Controllers\MyCourses\FavoriteController;
use App\Http\Controllers\MyCourses\InProgressController;
use App\Http\Controllers\NextLessonController;
use App\Http\Controllers\Platform\ExploreController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResetProgressController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Studio;
use App\Http\Controllers\TemporaryUploadController;
use App\Http\Controllers\TiptapImageController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureStudioCourseAccess;
use App\Http\Middleware\StudioMiddleware;
use Illuminate\Support\Facades\Route;

// Route::inertia('/tiptap', 'TiptapPage');

Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/explore', ExploreController::class)->name('explore');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses');
    Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{course:uuid}/enroll', EnrollCourseController::class)->name('courses.enroll');
    Route::post('/courses/{course:uuid}/access-requests', CourseAccessRequestController::class)->name('courses.access-requests.store');
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

    Route::prefix('admin')->middleware(AdminMiddleware::class)->group(function () {
        Route::get('/', Admin\AdminController::class)->name('admin');

        Route::name('admin.')->group(function () {
            Route::get('/settings', [Admin\SettingsController::class, 'index'])->name('settings');
            Route::patch('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');

            Route::get('/single-sign-on', [Admin\SSOProviderController::class, 'index'])->name('sso');
            Route::get('/single-sign-on/create', [Admin\SSOProviderController::class, 'create'])->name('sso.create');
            Route::get('/single-sign-on/{provider:uuid}', [Admin\SSOProviderController::class, 'edit'])->name('sso.edit');
            Route::patch('/single-sign-on/{provider:uuid}', [Admin\SSOProviderController::class, 'update'])->name('sso.update');
            Route::delete('/single-sign-on/{provider:uuid}', [Admin\SSOProviderController::class, 'destroy'])->name('sso.destroy');
            Route::post('/single-sign-on/{provider:uuid}/activate', Admin\ToggleSSOProviderController::class)->name('sso.activate');
            Route::post('/single-sign-on', [Admin\SSOProviderController::class, 'store'])->name('sso.store');

            Route::get('/invitations', [Admin\InvitationController::class, 'index'])->name('invitations');
            Route::post('/invitations', [Admin\InvitationController::class, 'store'])->name('invitations.store');

            Route::get('/users', [Admin\UserController::class, 'index'])->name('users');
            Route::get('/users/{user}', [Admin\UserController::class, 'edit'])->name('users.edit');
            Route::patch('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');

            Route::get('/groups', [Admin\AccessGroupController::class, 'index'])->name('groups');
            Route::get('/groups/create', [Admin\AccessGroupController::class, 'create'])->name('groups.create');
            Route::post('/groups', [Admin\AccessGroupController::class, 'store'])->name('groups.store');
            Route::get('/groups/{accessGroup}', [Admin\AccessGroupController::class, 'edit'])->name('groups.edit');
            Route::patch('/groups/{accessGroup}', [Admin\AccessGroupController::class, 'update'])->name('groups.update');
            Route::delete('/groups/{accessGroup}', [Admin\AccessGroupController::class, 'destroy'])->name('groups.destroy');
            Route::get('/groups/{accessGroup}/available-users', [Admin\AccessGroupController::class, 'availableUsers'])->name('groups.users.available');
            Route::post('/groups/{accessGroup}/users', [Admin\AccessGroupController::class, 'attachUsers'])->name('groups.users.store');
            Route::delete('/groups/{accessGroup}/users', [Admin\AccessGroupController::class, 'detachUsers'])->name('groups.users.destroy');

            Route::get('/categories', [Admin\CategoryController::class, 'index'])->name('categories');
            Route::get('/categories/create', [Admin\CategoryController::class, 'create'])->name('categories.create');
            Route::post('/categories', [Admin\CategoryController::class, 'store'])->name('categories.store');
            Route::get('/categories/{category}', [Admin\CategoryController::class, 'edit'])->name('categories.edit');
            Route::patch('/categories/{category}', [Admin\CategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('/instructors', [Admin\InstructorController::class, 'index'])->name('instructors');
            Route::post('/instructors', [Admin\InstructorController::class, 'store'])->name('instructors.store');
            Route::get('/instructors/create', [Admin\InstructorController::class, 'create'])->name('instructors.create');
            Route::get('/instructors/{instructor}', [Admin\InstructorController::class, 'edit'])->name('instructors.edit');
            Route::patch('/instructors/{instructor}', [Admin\InstructorController::class, 'update'])->name('instructors.update');
            Route::delete('/instructors/{instructor}', [Admin\InstructorController::class, 'destroy'])->name('instructors.destroy');

            Route::get('/courses', [Admin\CourseController::class, 'index'])->name('courses');
            Route::get('/videos', [Admin\VideoController::class, 'index'])->name('videos');

            Route::name('course-editor.')->middleware(EnsureStudioCourseAccess::class)
                ->group(__DIR__.'/course-editor.php');
        });
    });

    Route::get('/studio/users/lookup', Studio\UserLookupController::class)->name('studio.users.lookup');
    Route::get('/studio/users/search', Studio\UserLookupController::class)->name('studio.users.search');

    Route::prefix('studio')->middleware([StudioMiddleware::class, EnsureStudioCourseAccess::class])->group(function () {
        Route::get('/', Studio\StudioController::class)->name('studio');

        Route::name('studio.')->group(function () {
            Route::get('/video-uploads', [Studio\VideoUploadController::class, 'index'])->name('video-uploads');
            Route::get('/video-uploads/{videoUpload:uuid}', [Studio\VideoUploadController::class, 'show'])->name('video-uploads.show');
            Route::post('/video-uploads/{videoUpload:uuid}/complete', Studio\VideoUploadCompletionController::class)->name('video-uploads.complete');
            Route::get('/overview', Studio\OverviewController::class)->name('overview');

            Route::get('/courses', [Studio\CourseController::class, 'index'])->name('courses');
            Route::post('/courses', [Studio\CourseController::class, 'store'])->name('courses.store');
            Route::group([], __DIR__.'/course-editor.php');

            Route::get('/profile', [Studio\ProfileController::class, 'show'])->name('profile');
        });
    });

    Route::post('/files', [TemporaryUploadController::class, 'store'])->name('files.store');

    Route::post('/tiptap/images', [TiptapImageController::class, 'store'])->name('tiptap.images.store');
});

require __DIR__.'/auth.php';
