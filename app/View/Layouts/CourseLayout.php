<?php

namespace App\View\Layouts;

use App\Enums\CoursePreviewVersion;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Support\CoursePublication;
use Illuminate\Support\Facades\Gate;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;
use StackTrace\Ui\Link;

class CourseLayout extends StudioLayout
{
    public function __construct(protected Course $course, array $props = [])
    {
        parent::__construct($props);

        $this->breadcrumb([
            BreadcrumbItem::make(__('Courses'), Link::to(route('studio.courses'))),
            BreadcrumbItem::make($course->editorContent()->title ?: __('New Draft')),
        ]);
    }

    public function toLayout(): array
    {
        $problem = app(CoursePublication::class)->problem($this->course);

        return [
            ...parent::toLayout(),
            'id' => $this->course->uuid,
            'title' => $this->course->editorContent()->title,
            'slug' => $this->course->slug,
            'slugLocked' => $this->course->hasPublication(),
            'status' => $this->course->status,
            'isEditable' => Gate::allows('update', $this->course) && $this->course->canBeUpdated(),
            'canDelete' => Gate::allows('delete', $this->course),
            'canBeDeleted' => $this->course->canBeDeleted(),
            'isPublishing' => $this->course->status === CourseStatus::Publishing,
            'canPublish' => Gate::allows('publish', $this->course),
            'canBePublished' => $problem === null,
            'publicationProblem' => $problem,
            'hasUnpublishedChanges' => $this->course->draft?->has_changes ?? false,
            'hasCourseDraft' => $this->course->draft !== null,
            'revision' => $this->course->studio_revision,
            'canUnpublish' => Gate::allows('unpublish', $this->course),
            'canBeUnpublished' => $this->course->canBeUnpublished(),
            'canManageAccess' => Gate::allows('update', $this->course),
            'previewOptions' => CoursePreviewVersion::optionsFor($this->course),
        ];
    }
}
