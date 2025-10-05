<?php

namespace App\Http\Controllers\Studio;

use App\Enums\TextContentType;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\TemporaryUpload;
use App\Models\Video;
use App\Rules\FileListRule;
use App\Rules\TemporaryUploadRule;
use App\View\Layouts\CourseContentLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LessonController
{
    public function store(Course $course, Chapter $chapter)
    {
        $position = ($chapter->lessons()->max('position') ?: 0) + 1;

        $lesson = new Lesson([
            'position' => $position,
            'position_within_course' => 0,
            'description_type' => $course->author->getDefaultTextContentType(),
        ]);
        $lesson->chapter()->associate($chapter);
        $lesson->course()->associate($course);
        $lesson->save();

        return back();
    }

    public function show(Course $course, Lesson $lesson)
    {
        $chapter = $lesson->chapter;

        return Inertia::render('Studio/CourseContentLesson', CourseContentLayout::make($course, [
            'chapter' => [
                'id' => $chapter->uuid,
                'title' => $chapter->title,
                'fallbackTitle' => $chapter->getFallbackTitle(),
            ],
            'lesson' => [
                'id' => $lesson->uuid,
                'title' => $lesson->title,
                'fallbackTitle' => $lesson->getFallbackTitle(),
                'description' => $lesson->description,
                'descriptionType' => $lesson->description_type,
                'video' => $lesson->video?->getUrl(),
                'resources' => $lesson->resourceFiles()->all(),
                'isEditable' => $course->canBeUpdated(),
            ],
        ]));
    }

    public function update(Request $request, Course $course, Chapter $chapter, Lesson $lesson)
    {
        $resourceFiles = $lesson->resourceFiles();

        $request->validate([
            'title' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:5000'],
            'description_type' => ['required', 'string', Rule::enum(TextContentType::class)],
            'video' => TemporaryUploadRule::scope('CourseVideo'),
            'remove_video' => 'boolean',
            'resources' => FileListRule::make('CourseResource', $resourceFiles->getAdapter()),
        ]);

        DB::transaction(function () use ($request, $lesson, $resourceFiles) {
            $lesson->description = $request->input('description');
            $lesson->description_type = $request->enum('description_type', TextContentType::class);

            if ($title = $request->input('title')) {
                $lesson->title = $title;
                $lesson->slug = Str::slug($title);
            } else {
                $lesson->title = null;
                $lesson->slug = null;
            }

            $videoToRemove = null;
            $videoUploadToRemove = null;
            $removeVideo = $request->boolean('remove_video');
            $video = $request->input('video');

            if ($removeVideo && ($lesson->video)) {
                $videoToRemove = $lesson->video;
                $lesson->video()->disassociate();
            } elseif ($video) {
                if ($lesson->video) {
                    $videoToRemove = $lesson->video;
                }

                $videoUpload = TemporaryUpload::findOrFailByUuid($video);
                $lessonVideo = Video::create([
                    'file_path' => $videoUpload->copyToContentDisk('course-videos'),
                ]);
                $lesson->video()->associate($lessonVideo);
                $videoUploadToRemove = $videoUpload;
            }

            $lesson->save();

            $videoToRemove?->delete();
            $videoUploadToRemove?->delete();

            $resourceFiles->syncFromRequest('resources');
        });

        return back();
    }

    public function destroy(Course $course, Chapter $chapter, Lesson $lesson)
    {
        DB::transaction(fn () => $lesson->delete());

        return to_route('studio.course.chapters.show', [$course, $chapter]);
    }
}
