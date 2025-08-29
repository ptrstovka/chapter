<?php


namespace App\View\Layouts;


use App\Models\Course;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;
use StackTrace\Ui\Link;

class CourseLayout extends StudioLayout
{
    public function __construct(protected Course $course, array $props)
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
        ];
    }
}
