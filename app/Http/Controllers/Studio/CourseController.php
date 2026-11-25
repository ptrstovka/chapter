<?php

namespace App\Http\Controllers\Studio;

use App\Enums\CourseStatus;
use App\Enums\TextContentType;
use App\Models\Category;
use App\Models\Course;
use App\Models\TemporaryUpload;
use App\Rules\TemporaryUploadRule;
use App\Support\CourseDrafts;
use App\Support\Slug;
use App\Table\CourseTable;
use App\View\Layouts\CourseLayout;
use App\View\Layouts\StudioLayout;
use App\View\Models\VideoSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;
use StackTrace\Ui\SelectOption;

class CourseController
{
    public function index()
    {
        $courses = new CourseTable(
            author: Auth::user()->author,
        );

        return Inertia::render('Studio/CourseList', StudioLayout::make([
            'courses' => $courses,
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
        $content = $course->editorContent();

        return Inertia::render('Studio/CourseDetail', CourseLayout::make($course, [
            'description' => $content->description,
            'descriptionType' => $content->description_type,
            'coverImage' => $content->getCoverImageUrl(),
            'trailer' => VideoSource::for($content->trailer),
            'category' => $content->category_id,
            'categories' => Category::query()
                ->get()
                ->map(fn (Category $category) => new SelectOption($category->title, $category->id))
                ->sortBy('label')
                ->values(),
        ]));
    }

    public function update(Request $request, Course $course, CourseDrafts $drafts)
    {
        Gate::authorize('update', $course);

        $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'slug' => $course->hasPublication()
                ? ['nullable', Rule::in([$course->slug])]
                : ['nullable', 'string', 'max:191', 'regex:/^[a-z0-9-]+$/', Rule::unique(Course::class, 'slug')->ignoreModel($course)],
            'description' => ['nullable', 'string', 'max:5000'],
            'description_type' => ['required', 'string', Rule::enum(TextContentType::class)],
            'cover_image' => TemporaryUploadRule::scope('CourseCoverImage'),
            'remove_cover_image' => 'boolean',
            'category' => ['required', 'numeric', Rule::exists(Category::class, 'id')],
        ]);

        $content = $course->editorContent();
        $title = $request->input('title');
        $slug = $request->input('slug');

        $content->category()->associate($request->input('category'));

        if (! $course->hasPublication()) {
            if ($request->filled('slug') && $slug !== $course->slug) {
                $course->slug = $slug;
            } elseif (! $course->slug || ($course->title && $title !== $course->title && $course->status === CourseStatus::Draft)) {
                $course->slug = Slug::unique($title, Course::class, 'slug');
            }
        }

        $content->title = $title;
        $content->description = $request->input('description');
        $content->description_type = $request->enum('description_type', TextContentType::class);

        $coverImageToRemove = null;
        $coverImageUploadToRemove = null;
        $removeCoverImage = $request->boolean('remove_cover_image');
        $coverImage = $request->input('cover_image');

        if ($removeCoverImage) {
            $coverImageToRemove = $content->cover_image_file_path;
            $content->cover_image_file_path = null;
        } elseif ($coverImage) {
            $coverImageUpload = TemporaryUpload::findOrFailByUuid($coverImage);
            $coverImageToRemove = $content->cover_image_file_path;
            $content->cover_image_file_path = $drafts->copyUpload($coverImageUpload, 'course-covers');
            $coverImageUploadToRemove = $coverImageUpload;
        }

        $content->save();

        $drafts->deleteUnusedFile($coverImageToRemove);
        $coverImageUploadToRemove?->delete();

        return back();
    }

    public function destroy(Course $course)
    {
        DB::transaction(fn () => $course->delete());

        return to_route('studio.courses');
    }
}
