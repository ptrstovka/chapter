<?php

namespace App\Table;

use App\Enums\CourseStatus;
use App\Models\Author;
use App\Models\Course;
use App\Table\Actions\PublishCourseAction;
use App\Table\Actions\UnpublishCourseAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use StackTrace\Ui\DateRange;
use StackTrace\Ui\Link;
use StackTrace\Ui\ModelSelection;
use StackTrace\Ui\SelectOption;
use StackTrace\Ui\Table;
use StackTrace\Ui\Table\Actions;
use StackTrace\Ui\Table\Columns;
use StackTrace\Ui\Table\Filters;

class CourseTable extends Table
{
    public function __construct(
        protected ?Author $author
    ) {
        $this->searchable(fn (Builder $builder, string $term) => $builder->search($term));

        $this->defaultSorting(function (Builder $builder) {
            $builder
                ->orderByRaw("CASE WHEN status = 'draft' THEN 0 ELSE 1 END ASC")
                ->orderByDesc('created_at');
        });
    }

    public function source(): Builder
    {
        return Course::query()
            ->when($this->author, fn (Builder $builder, Author $author) => $builder->whereBelongsTo($author))
            ->with(['author'])
            ->withCount(['chapters', 'lessons', 'enrollments']);
    }

    public function columns(): array
    {
        return collect([
            Columns\Text::make(__('Title'), fn (Course $course) => $course->title ?: __('Draft'))
                ->link(fn (Course $course) => $this->author ? Link::to(route('studio.courses.show', $course)) : null)
                ->style(function (Table\Style $style, Course $course) {
                    if (! $course->title) {
                        $style->colorMuted();
                    } else {
                        $style->fontMedium();
                    }
                }),

            $this->author
                ? null
                : Columns\Text::make(__('Author'), fn (Course $course) => $course->author->name)->width(56),

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
        ])->filter()->values()->all();
    }

    public function filters(): array
    {
        return collect([
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

            $this->author
                ? null
                : Filters\Model::make(__('Author'), Author::class, 'author')
                    ->options(Author::query()->orderBy('name')->get())
                    ->label('name')
                    ->using(fn (Builder $builder, ModelSelection $selection) => $selection->applyOnBuilder($builder, 'author_id')),

            Filters\DateRange::make(__('Created At'), 'created')
                ->using(fn (Builder $builder, DateRange $range) => $range->applyToQuery($builder, 'created_at')),
        ])->filter()->values()->all();
    }

    public function actions(): array
    {
        return collect([
            $this->author
                ? Actions\Link::make(__('Manage'), fn (Course $course) => route('studio.courses.show', $course))
                : null,

            PublishCourseAction::make()
                ->can(fn (Course $course) => Gate::allows('publish', $course) && $course->canBePublished())
                ->bulk(),

            UnpublishCourseAction::make()
                ->can(fn (Course $course) => Gate::allows('unpublish', $course) && $course->canBeUnpublished())
                ->bulk(),

            Actions\Link::make(__('View on Platform'), fn (Course $course) => $course->slug ? route('courses.show', $course->slug) : '')
                ->can(fn (Course $course) => $course->slug && $course->status === CourseStatus::Published),
        ])->filter()->values()->all();
    }
}
