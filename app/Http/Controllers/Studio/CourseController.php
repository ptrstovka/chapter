<?php

namespace App\Http\Controllers\Studio;

use App\Enums\CourseStatus;
use App\Enums\TextContentType;
use App\Models\Course;
use App\Models\TemporaryUpload;
use App\Models\Video;
use App\Rules\TemporaryUploadRule;
use App\Support\Slug;
use App\View\Layouts\CourseLayout;
use App\View\Layouts\StudioLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;

class CourseController
{
    public function index()
    {
        return Inertia::render('Studio/CourseList', StudioLayout::make([
            //
        ])->breadcrumb(BreadcrumbItem::make(__('Courses'))));
    }

    public function store()
    {
        Gate::authorize('create', Course::class);

        $author = Auth::user()->author;

        $course = new Course([
            'status' => CourseStatus::Draft,
            'description_type' => $author->getDefaultTextContentType(),
        ]);

        $course->author()->associate($author);

        $course->save();

        return to_route('studio.courses.show', $course);
    }

    public function show(Course $course)
    {
        Gate::authorize('view', $course);

        return Inertia::render('Studio/CourseDetail', CourseLayout::make($course, [
            'description' => $course->description,
            'descriptionType' => $course->description_type,
            'coverImage' => $course->getCoverImageUrl(),
            'trailer' => $course->trailer?->getUrl(),
        ])->breadcrumb(BreadcrumbItem::make(__('General'))));
    }

    public function update(Request $request, Course $course)
    {
        Gate::authorize('update', $course);

        $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:191', 'regex:/^[a-z0-9-]+$/', Rule::unique(Course::class, 'slug')->ignoreModel($course)],
            'description' => ['nullable', 'string', 'max:5000'],
            'description_type' => ['required', 'string', Rule::enum(TextContentType::class)],
            'cover_image' => TemporaryUploadRule::scope('CourseCoverImage'),
            'remove_cover_image' => 'boolean',
            'trailer' => TemporaryUploadRule::scope('CourseTrailerVideo'),
            'remove_trailer' => 'boolean',
        ]);

        DB::transaction(function () use ($course, $request) {
            $title = $request->input('title');
            $slug = $request->input('slug');

            if ($slug != $course->slug) {
                $course->slug = $slug;
            } elseif (! $course->slug) {
                $course->slug = Slug::unique($title, Course::class, 'slug');
            } elseif ($course->title && $course->slug && $title != $course->title && $slug == $course->slug && $course->status === CourseStatus::Draft) {
                $course->slug = Slug::unique($title, Course::class, 'slug');
            }

            $course->title = $title;
            $course->description = $request->input('description');
            $course->description_type = $request->enum('description_type', TextContentType::class);

            $coverImageToRemove = null;
            $coverImageUploadToRemove = null;
            $removeCoverImage = $request->boolean('remove_cover_image');
            $coverImage = $request->input('cover_image');

            if ($removeCoverImage && ($course->cover_image_file_path)) {
                $coverImageToRemove = $course->cover_image_file_path;
                $course->cover_image_file_path = null;
            } elseif ($coverImage) {
                if ($course->cover_image_file_path) {
                    $coverImageToRemove = $course->cover_image_file_path;
                }

                $coverImageUpload = TemporaryUpload::findOrFailByUuid($coverImage);
                $course->cover_image_file_path = $coverImageUpload->copyTo('public', 'course-covers');
                $coverImageUploadToRemove = $coverImageUpload;
            }

            $trailerVideoToRemove = null;
            $trailerVideoUploadToRemove = null;
            $removeTrailer = $request->boolean('remove_trailer');
            $trailerVideo = $request->input('trailer');

            if ($removeTrailer && ($course->trailer)) {
                $trailerVideoToRemove = $course->trailer;
                $course->trailer()->disassociate();
            } elseif ($trailerVideo) {
                if ($course->trailer) {
                    $trailerVideoToRemove = $course->trailer;
                }

                $trailerVideoUpload = TemporaryUpload::findOrFailByUuid($trailerVideo);
                $trailer = Video::create([
                    'file_path' => $trailerVideoUpload->copyTo('public', 'course-videos'),
                ]);
                $course->trailer()->associate($trailer);
                $trailerVideoUploadToRemove = $trailerVideoUpload;
            }

            $course->save();

            if ($coverImageToRemove) {
                Storage::disk('public')->delete($coverImageToRemove);
            }
            $coverImageUploadToRemove?->delete();
            $trailerVideoToRemove?->delete();
            $trailerVideoUploadToRemove?->delete();
        });

        return back();
    }
}
