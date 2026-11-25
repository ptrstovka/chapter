<?php

namespace App\Enums;

use App\Models\Course;
use App\Models\CourseDraft;

enum CoursePreviewVersion: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(Course $course): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Published => $course->status === CourseStatus::Published
                ? __('Published version')
                : __('Last published version'),
        };
    }

    public function isAvailableFor(Course $course): bool
    {
        return match ($this) {
            self::Draft => ! $course->hasPublication() || ($course->draft?->has_changes ?? false),
            self::Published => $course->hasPublication(),
        };
    }

    public function content(Course $course): Course|CourseDraft
    {
        abort_unless($this->isAvailableFor($course), 404);

        return $this === self::Draft ? $course->editorContent() : $course;
    }

    /** @return list<array{label: string, url: string}> */
    public static function optionsFor(Course $course, CourseEditorContext $context): array
    {
        return collect(self::cases())
            ->filter(fn (self $version) => $version->isAvailableFor($course))
            ->map(fn (self $version) => [
                'label' => $version->label($course),
                'url' => route($context->routeName('courses.preview.show'), [$course, $version]),
            ])
            ->values()
            ->all();
    }

    /** @return array{version: string, versionLabel: string, courseUrl: string, studioUrl: string, returnLabel: string} */
    public function pageProps(Course $course, CourseEditorContext $context): array
    {
        return [
            'version' => $this->value,
            'versionLabel' => $this->label($course),
            'courseUrl' => route($context->routeName('courses.preview.show'), [$course, $this]),
            'studioUrl' => route($context->routeName('courses.show'), $course),
            'returnLabel' => $context === CourseEditorContext::Admin ? __('Back to editor') : __('Back to Studio'),
        ];
    }
}
