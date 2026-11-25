<?php

namespace App\Http\Controllers\Studio;

use App\Enums\CoursePreviewVersion;
use App\Models\Course;
use App\View\Models\CoursePresentation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CoursePreviewController
{
    public function show(Course $course, CoursePreviewVersion $version, CoursePresentation $presentation): Response
    {
        return Inertia::render('Courses/CourseDetail', [
            ...$presentation->detail($course, $version->content($course), $version),
            'preview' => $version->pageProps($course),
            'access' => null,
            'enrollment' => null,
        ]);
    }

    public function lesson(Course $course, CoursePreviewVersion $version, string $lessonUuid, CoursePresentation $presentation): Response
    {
        $content = $version->content($course);
        $lesson = $content->lessons()->where('uuid', $lessonUuid)->firstOrFail();

        return Inertia::render('Courses/CourseLesson', [
            ...$presentation->lesson($course, $content, $lesson, $version),
            'preview' => $version->pageProps($course),
        ]);
    }

    public function resource(Course $course, CoursePreviewVersion $version, string $lessonUuid, string $resourceUuid): StreamedResponse
    {
        $content = $version->content($course);
        $lesson = $content->lessons()->where('uuid', $lessonUuid)->firstOrFail();
        $resources = $lesson->resources();
        $resource = $resources
            ->where($resources->getRelated()->qualifyColumn('course_id'), $content->id)
            ->where($resources->getRelated()->qualifyColumn('uuid'), $resourceUuid)
            ->firstOrFail();

        abort_unless(Storage::disk(App::contentDisk())->exists($resource->file_path), 404);

        return $resource->download();
    }
}
