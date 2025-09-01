<?php

namespace App\View\Layouts;

use App\Enums\CourseStatus;
use App\Models\Course;
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
            BreadcrumbItem::make($course->title ?: __('New Draft')),
        ]);
    }

    public function toLayout(): array
    {
        return [
            ...parent::toLayout(),
            'id' => $this->course->uuid,
            'title' => $this->course->title,
            'slug' => $this->course->slug,
            'status' => $this->course->status,
            'isEditable' => Gate::allows('update', $this->course) && $this->course->canBeUpdated(),
            'canDelete' => Gate::allows('delete', $this->course),
            'canBeDeleted' => $this->course->canBeDeleted(),
            'isPublishing' => in_array($this->course->status, [CourseStatus::Publishing, CourseStatus::PublishFailure]),
            'canPublish' => Gate::allows('publish', $this->course),
            'canBePublished' => $this->course->canBePublished(),
            'canUnpublish' => Gate::allows('unpublish', $this->course),
            'canBeUnpublished' => $this->course->canBeUnpublished(),
        ];
    }
}
