<?php

namespace App\Table;

use App\Enums\VideoProcessingStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use StackTrace\Ui\SelectOption;
use StackTrace\Ui\Table;
use StackTrace\Ui\Table\Columns;
use StackTrace\Ui\Table\Filters;

class VideoTable extends Table
{
    public function __construct()
    {
        $this->withoutResource();

        $this->searchable(function (Builder $query, string $term) {
            $query->where(function (Builder $query) use ($term) {
                $query
                    ->whereHas('lessons', fn (Builder $query) => $query->whereLike('title', "%{$term}%"))
                    ->orWhereHas('lessons.chapter', fn (Builder $query) => $query->whereLike('title', "%{$term}%"))
                    ->orWhereHas('lessons.course', fn (Builder $query) => $query->withTrashed()->whereLike('title', "%{$term}%"))
                    ->orWhereHas('trailerCourses', fn (Builder $query) => $query->whereLike('title', "%{$term}%"));

                if (ctype_digit($term)) {
                    $query->orWhere('videos.id', $term);
                }
            });
        });

        $this->defaultSorting(fn (Builder $query) => $query->orderByDesc('id'));
    }

    public function source(): Builder
    {
        return Video::query()->with([
            'lessons' => fn (HasMany $query) => $query->orderBy('course_id')->orderBy('position_within_course'),
            'lessons.course' => fn (BelongsTo $query) => $query->withTrashed(),
            'lessons.chapter',
            'trailerCourses',
            'latestCompletedUpload',
        ]);
    }

    public function columns(): array
    {
        return [
            Columns\Text::make('ID', 'id')->sortable(using: 'id', named: 'id')->numsTabular(),

            Columns\Text::make(__('Course'), fn (Video $video) => $this->usages($video)->pluck('course')->join("\n") ?: '—')
                ->fontMedium()->whitespacePreLine()->minWidth(48),

            Columns\Text::make(__('Video type'), fn (Video $video) => $this->usages($video)->pluck('type')->join("\n") ?: __('Unassigned'))
                ->whitespacePreLine(),

            Columns\Text::make(__('Chapter / lesson'), fn (Video $video) => $this->usages($video)->pluck('lesson')->join("\n") ?: '—')
                ->whitespacePreLine()->minWidth(48),

            Columns\Badge::make(__('Status'), fn (Video $video) => $video->processing_status->value)
                ->label($this->statusLabels())
                ->variant([
                    VideoProcessingStatus::Queued->value => 'default',
                    VideoProcessingStatus::Processing->value => 'warning',
                    VideoProcessingStatus::Ready->value => 'positive',
                    VideoProcessingStatus::Failed->value => 'destructive',
                ]),

            Columns\Badge::make(__('HLS stream'), fn (Video $video) => $video->hls_playlist_path ? 'yes' : 'no')
                ->label(['yes' => __('Yes'), 'no' => __('No')])
                ->variant(['yes' => 'positive', 'no' => 'default']),

            Columns\Badge::make(__('Original'), fn (Video $video) => $video->file_path ? 'yes' : 'no')
                ->label(['yes' => __('Yes'), 'no' => __('No')])
                ->variant(['yes' => 'positive', 'no' => 'default']),

            Columns\Text::make(__('Original size'), function (Video $video) {
                if (! $video->file_path) {
                    return '—';
                }

                return $video->latestCompletedUpload
                    ? Number::fileSize($video->latestCompletedUpload->size)
                    : __('Unknown');
            })->alignRight()->numsTabular(),

            Columns\Text::make(__('HLS size'), function (Video $video) {
                if (! $video->hls_playlist_path) {
                    return '—';
                }

                return $video->hls_size_bytes !== null
                    ? Number::fileSize($video->hls_size_bytes)
                    : __('Unknown');
            })->alignRight()->numsTabular(),

            Columns\Text::make(__('Duration'), fn (Video $video) => $video->getDurationLabel() ?? '—')
                ->sortable(using: 'duration_seconds', named: 'duration')->alignRight()->numsTabular(),

            Columns\DateTime::make(__('Created At'), 'created_at')->sortable(using: 'created_at', named: 'created'),
        ];
    }

    public function filters(): array
    {
        return [
            Filters\Select::make(__('Video type'), 'type', [
                new SelectOption(__('Lesson'), 'lesson'),
                new SelectOption(__('Trailer'), 'trailer'),
                new SelectOption(__('Unassigned'), 'unassigned'),
            ])->using(function (Builder $query, array $selection) {
                $values = collect($selection)->pluck('value');

                $query->where(function (Builder $query) use ($values) {
                    if ($values->contains('lesson')) {
                        $query->orWhereHas('lessons');
                    }

                    if ($values->contains('trailer')) {
                        $query->orWhereHas('trailerCourses');
                    }

                    if ($values->contains('unassigned')) {
                        $query->orWhere(fn (Builder $query) => $query->whereDoesntHave('lessons')->whereDoesntHave('trailerCourses'));
                    }
                });
            }),

            $this->presenceFilter(__('HLS stream'), 'hls', 'hls_playlist_path'),
            $this->presenceFilter(__('Original'), 'original', 'file_path'),

            Filters\Select::make(__('Status'), 'status', collect($this->statusLabels())
                ->map(fn (string $label, string $value) => new SelectOption($label, $value))->values()
            )->using(fn (Builder $query, array $selection) => $query->whereIn('processing_status', collect($selection)->pluck('value'))),
        ];
    }

    protected function presenceFilter(string $title, string $field, string $column): Filters\Select
    {
        return Filters\Select::make($title, $field, [
            new SelectOption(__('Yes'), 'yes'),
            new SelectOption(__('No'), 'no'),
        ])->using(function (Builder $query, array $selection) use ($column) {
            $values = collect($selection)->pluck('value')->unique();

            if ($values->count() === 1) {
                if ($values->first() === 'yes') {
                    $query->whereNotNull($column)->where($column, '!=', '');
                } else {
                    $query->where(fn (Builder $query) => $query->whereNull($column)->orWhere($column, ''));
                }
            }
        });
    }

    /** @return array<string, string> */
    protected function statusLabels(): array
    {
        return [
            VideoProcessingStatus::Queued->value => __('Queued'),
            VideoProcessingStatus::Processing->value => __('Processing'),
            VideoProcessingStatus::Ready->value => __('Ready'),
            VideoProcessingStatus::Failed->value => __('Failed'),
        ];
    }

    /** @return Collection<int, array{course: string, type: string, lesson: string}> */
    protected function usages(Video $video): Collection
    {
        return $video->lessons->map(fn (Lesson $lesson) => [
            'course' => $lesson->course?->title ?: __('Draft'),
            'type' => __('Lesson'),
            'lesson' => collect([
                $lesson->chapter ? ($lesson->chapter->title ?: $lesson->chapter->getFallbackTitle()) : null,
                $lesson->title ?: $lesson->getFallbackTitle(),
            ])->filter()->join(' / '),
        ])->concat($video->trailerCourses->map(fn (Course $course) => [
            'course' => $course->title ?: __('Draft'),
            'type' => __('Trailer'),
            'lesson' => '—',
        ]));
    }
}
