<?php

namespace App\Http\Controllers\Studio;

use App\Enums\CourseStatus;
use App\Enums\TextContentType;
use App\Models\Author;
use App\Models\Category;
use App\Models\Course;
use App\Models\TemporaryUpload;
use App\Models\Video;
use App\Rules\TemporaryUploadRule;
use App\Support\Slug;
use App\Table\Actions\PublishCourseAction;
use App\Table\Actions\UnpublishCourseAction;
use App\View\Layouts\CourseLayout;
use App\View\Layouts\StudioLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;
use StackTrace\Ui\DateRange;
use StackTrace\Ui\Link;
use StackTrace\Ui\ModelSelection;
use StackTrace\Ui\SelectOption;
use StackTrace\Ui\Table;
use StackTrace\Ui\Table\Actions;
use StackTrace\Ui\Table\Columns;
use StackTrace\Ui\Table\Filters;

class CourseController
{
    public function index()
    {
        $builder = Course::query()
            ->with(['author'])
            ->withCount(['chapters', 'lessons', 'enrollments']);

        $courses = Table::make($builder)
            ->searchable(fn (Builder $builder, string $term) => $builder->search($term))
            ->defaultSorting(function (Builder $builder) {
                $builder
                    ->orderByRaw("CASE WHEN status = 'draft' THEN 0 ELSE 1 END ASC")
                    ->orderByDesc('created_at');
            })
            ->withColumns([
                Columns\Text::make(__('Title'), fn (Course $course) => $course->title ?: __('Draft'))
                    ->link(fn (Course $course) => Link::to(route('studio.courses.show', $course)))
                    ->style(function (Table\Style $style, Course $course) {
                        if (! $course->title) {
                            $style->colorMuted();
                        } else {
                            $style->fontMedium();
                        }
                    }),

                Auth::user()->is_admin
                    ? Columns\Text::make(__('Author'), fn (Course $course) => $course->author->name)->width(56)
                    : null,

                Columns\Badge::make(__('Status'), fn (Course $course) => $course->status->value)
                    ->variant([
                        CourseStatus::Draft->value => 'default',
                        CourseStatus::Publishing->value => 'default',
                        CourseStatus::PublishFailure->value => 'default',
                        CourseStatus::Unpublished->value => 'warning',
                        CourseStatus::Published->value => 'positive',
                    ])
                    ->label([
                        CourseStatus::Draft->value => __('Draft'),
                        CourseStatus::Publishing->value => __('Publishing'),
                        CourseStatus::PublishFailure->value => __('Publishing'),
                        CourseStatus::Unpublished->value => __('Unpublished'),
                        CourseStatus::Published->value => __('Published'),
                    ])
                    ->width(28),

                Columns\Text::make(__('Enrollments'), fn (Course $course) => $course->enrollments_count)
                    ->width(28)
                    ->alignRight()
                    ->numsTabular()
                    ->sortable(using: 'enrollments_count', named: 'enrollments'),

                Columns\Text::make(__('Chapters'), fn (Course $course) => $course->chapters_count)
                    ->width(28)
                    ->alignRight()
                    ->numsTabular()
                    ->sortable(using: 'chapters_count', named: 'chapters'),

                Columns\Text::make(__('Lessons'), fn (Course $course) => $course->lessons_count)
                    ->width(28)
                    ->alignRight()
                    ->numsTabular()
                    ->sortable(using: 'lessons_count', named: 'lessons'),

                Columns\DateTime::make(__('Created At'), 'created_at')
                    ->sortable(using: 'created_at', named: 'created'),
            ])
            ->withActions([
                Actions\Link::make(__('Manage'), fn (Course $course) => Link::to(route('studio.courses.show', $course))),

                PublishCourseAction::make()
                    ->can(fn (Course $course) => Gate::allows('publish', $course) && $course->canBePublished())
                    ->bulk(),

                UnpublishCourseAction::make()
                    ->can(fn (Course $course) => Gate::allows('unpublish', $course) && $course->canBeUnpublished())
                    ->bulk(),

                Actions\Link::make(__('View on Platform'), fn (Course $course) => $course->slug ? Link::to(route('courses.show', $course)) : '')
                    ->can(fn (Course $course) => $course->slug && $course->status === CourseStatus::Published),
            ])
            ->withFilters([
                Filters\Select::make(__('Status'), 'status', [
                    new SelectOption(__('Draft'), 'draft'),
                    new SelectOption(__('Publishing'), 'publishing'),
                    new SelectOption(__('Published'), 'published'),
                    new SelectOption(__('Unpublished'), 'unpublished'),
                ])->using(function (Builder $builder, array $selection) {
                    $values = collect($selection)->pluck('value')->all();

                    $builder->where(function (Builder $builder) use ($values) {
                        if (in_array('draft', $values)) {
                            $builder->orWhere('status', CourseStatus::Draft);
                        }

                        if (in_array('unpublished', $values)) {
                            $builder->orWhere('status', CourseStatus::Unpublished);
                        }

                        if (in_array('publishing', $values)) {
                            $builder->orWhere(fn (Builder $builder) => $builder
                                ->where('status', CourseStatus::Publishing)
                                ->orWhere('status', CourseStatus::PublishFailure)
                            );
                        }

                        if (in_array('published', $values)) {
                            $builder->orWhere('status', CourseStatus::Published);
                        }
                    });
                }),

                Auth::user()->is_admin
                    ? Filters\Model::make(__('Author'), Author::class, 'author')
                        ->options(Author::query()->orderBy('name')->get())
                        ->label('name')
                        ->using(fn (Builder $builder, ModelSelection $selection) => $selection->applyOnBuilder($builder, 'author_id'))
                    : null,

                Filters\DateRange::make(__('Created At'), 'created')
                    ->using(fn (Builder $builder, DateRange $range) => $range->applyToQuery($builder, 'created_at')),
            ]);

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

        return Inertia::render('Studio/CourseDetail', CourseLayout::make($course, [
            'description' => $course->description,
            'descriptionType' => $course->description_type,
            'coverImage' => $course->getCoverImageUrl(),
            'trailer' => $course->trailer?->getUrl(),
            'category' => $course->category?->id,
            'categories' => Category::query()
                ->get()
                ->map(fn (Category $category) => new SelectOption($category->title, $category->id))
                ->sortBy('label')
                ->values(),
        ]));
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
            'category' => ['required', 'numeric', Rule::exists(Category::class, 'id')],
        ]);

        DB::transaction(function () use ($course, $request) {
            $title = $request->input('title');
            $slug = $request->input('slug');

            $course->category()->associate($request->input('category'));

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

    public function destroy(Course $course)
    {
        DB::transaction(fn () => $course->delete());

        return to_route('studio.courses');
    }
}
