<?php

namespace App\Http\Controllers\Studio;

use App\Enums\TextContentType;
use App\Models\Course;
use App\Rules\FileListRule;
use App\View\Layouts\CourseContentLayout;
use App\View\Models\VideoSource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LessonController
{
    public function store(Request $request, Course $course)
    {
        $chapter = $request->route('chapter');
        $position = ($chapter->lessons()->max('position') ?: 0) + 1;

        $course->editorContent()->lessons()->create([
            'chapter_id' => $chapter->id,
            'position' => $position,
            'position_within_course' => 0,
            'description_type' => $course->author->getDefaultTextContentType(),
        ]);

        return back();
    }

    public function show(Request $request, Course $course)
    {
        $lesson = $request->route('lesson');
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
                'video' => VideoSource::for($lesson->video),
                'resources' => $lesson->resourceFiles()->all(),
                'isEditable' => $course->canBeUpdated(),
            ],
        ]));
    }

    public function update(Request $request, Course $course)
    {
        $lesson = $request->route('lesson');
        $resourceFiles = $lesson->resourceFiles();

        $request->validate([
            'title' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:5000'],
            'description_type' => ['required', 'string', Rule::enum(TextContentType::class)],
            'resources' => FileListRule::make('CourseResource', $resourceFiles->getAdapter()),
        ]);

        $lesson->description = $request->input('description');
        $lesson->description_type = $request->enum('description_type', TextContentType::class);

        if ($title = $request->input('title')) {
            $lesson->title = $title;
            $lesson->slug = Str::slug($title);
        } else {
            $lesson->title = null;
            $lesson->slug = null;
        }

        $lesson->save();

        $resourceFiles->syncFromRequest('resources');

        return back();
    }

    public function destroy(Request $request, Course $course)
    {
        $request->route('lesson')->delete();

        return to_route('studio.course.chapters.show', [$course, $request->route('chapter')->uuid]);
    }
}
