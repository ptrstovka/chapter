<?php

namespace App\View\Models;

use App\Enums\CoursePreviewVersion;
use App\Enums\TextContentType;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\CourseDraft;
use App\Models\Lesson;
use App\Models\Resource;
use App\Support\TextRenderer;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class CoursePresentation
{
    public function __construct(private TextRenderer $text) {}

    /** @return array<string, mixed> */
    public function detail(Course $course, Course|CourseDraft $content, ?CoursePreviewVersion $preview = null): array
    {
        $content->loadMissing(['trailer', 'chapters.lessons.video']);

        return [
            'id' => $course->uuid,
            'slug' => $course->slug,
            'title' => $this->courseTitle($content),
            'trailer' => VideoSource::for($content->trailer),
            'description' => $this->description($content->description, $content->description_type),
            'author' => [
                'name' => $course->author->name,
                'avatarUrl' => $course->author->getAvatarUrl(),
                'bio' => $course->author->bio,
            ],
            'chapters' => $content->chapters->sortBy('position')->values()->map(fn (Chapter $chapter) => [
                'id' => $chapter->uuid,
                'title' => filled($chapter->title) ? $chapter->title : $chapter->getFallbackTitle(),
                'lessons' => $chapter->lessons->sortBy('position')->values()->map(fn (Lesson $lesson) => [
                    'id' => $lesson->uuid,
                    'title' => $lesson->getDisplayTitle(),
                    'duration' => $lesson->video?->getDurationLabel(),
                    ...($preview ? ['url' => route('studio.courses.preview.lesson', [$course, $preview, $lesson->uuid])] : []),
                ]),
            ]),
        ];
    }

    /**
     * @param  array<int>  $completedLessonIds
     * @return array<string, mixed>
     */
    public function lesson(
        Course $course,
        Course|CourseDraft $content,
        Lesson $currentLesson,
        ?CoursePreviewVersion $preview = null,
        array $completedLessonIds = [],
    ): array {
        $content->loadMissing('chapters.lessons.video');
        $currentLesson->loadMissing(['video', 'resources']);

        $lessonNo = 0;
        $completed = array_fill_keys($completedLessonIds, true);
        $chapters = $content->chapters->sortBy('position')->values()
            ->map(function (Chapter $chapter, int $idx) use (&$lessonNo, $currentLesson, $course, $completed, $preview) {
                return [
                    'no' => $idx + 1,
                    'title' => filled($chapter->title) ? $chapter->title : $chapter->getFallbackTitle(),
                    'lessons' => $chapter->lessons->sortBy('position')->values()
                        ->map(function (Lesson $lesson) use (&$lessonNo, $currentLesson, $course, $completed, $preview) {
                            return [
                                'slugId' => $preview ? $lesson->uuid : $lesson->slug_id,
                                'no' => ++$lessonNo,
                                'title' => $lesson->getDisplayTitle(),
                                'isCurrent' => $lesson->is($currentLesson),
                                'duration' => $lesson->video?->getDurationLabel(),
                                'url' => $preview
                                    ? route('studio.courses.preview.lesson', [$course, $preview, $lesson->uuid])
                                    : route('lessons.show', [$course->slug, $lesson->slug_id]),
                                'isCompleted' => isset($completed[$lesson->id]),
                            ];
                        }),
                ];
            });

        return [
            'id' => $currentLesson->uuid,
            'isCompleted' => isset($completed[$currentLesson->id]),
            'completedLessons' => count($completed),
            'totalLessons' => $lessonNo,
            'remainingLessons' => $lessonNo - count($completed),
            'courseSlug' => $preview ? null : $course->slug,
            'courseTitle' => $this->courseTitle($content),
            'lessonTitle' => $currentLesson->getDisplayTitle(),
            'video' => VideoSource::for($currentLesson->video),
            'description' => $this->description($currentLesson->description, $currentLesson->description_type),
            'resources' => $currentLesson->resources->map(fn (Resource $resource) => [
                'name' => $resource->client_file_name,
                'url' => $preview
                    ? route('studio.courses.preview.resource', [$course, $preview, $currentLesson->uuid, $resource->uuid])
                    : route('resources.show', [$course->slug, $resource->uuid]),
                'size' => Number::fileSize($resource->size),
            ]),
            'chapters' => $chapters,
        ];
    }

    private function courseTitle(Course|CourseDraft $content): string
    {
        return Str::trim($content->title ?? '') === '' ? __('Untitled course') : $content->title;
    }

    private function description(?string $description, ?TextContentType $type): ?string
    {
        if (blank($description)) {
            return null;
        }

        $html = $this->text->toHtml($description, $type ?? TextContentType::Html);
        $visibleContent = strip_tags($html, '<img><video><audio><iframe><hr><svg><object><embed>');
        $visibleContent = html_entity_decode($visibleContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return filled(Str::squish($visibleContent)) ? $html : null;
    }
}
