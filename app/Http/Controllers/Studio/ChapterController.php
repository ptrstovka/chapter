<?php

namespace App\Http\Controllers\Studio;

use App\Enums\CourseEditorContext;
use App\Models\Course;
use App\Models\Lesson;
use App\View\Layouts\CourseContentLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ChapterController
{
    public function store(Request $request, Course $course)
    {
        $lessonOrChapter = DB::transaction(function () use ($request, $course) {
            $content = $course->editorContent();
            $chapter = $content->chapters()->create([
                'position' => ($content->chapters()->max('position') ?: 0) + 1,
            ]);

            if ($request->has('lesson')) {
                return $content->lessons()->create([
                    'chapter_id' => $chapter->id,
                    'position' => 1,
                    'description_type' => $course->author->getDefaultTextContentType(),
                    'position_within_course' => 0,
                ]);
            }

            return $chapter;
        });

        $route = $lessonOrChapter instanceof Lesson ? 'course.lessons.show' : 'course.chapters.show';

        return to_route(CourseEditorContext::forRequest($request)->routeName($route), [$course, $lessonOrChapter->uuid]);
    }

    public function show(Request $request, Course $course)
    {
        $chapter = $request->route('chapter');

        return Inertia::render('Studio/CourseContentChapter', CourseContentLayout::make($course, [
            'chapter' => [
                'id' => $chapter->uuid,
                'title' => $chapter->title,
                'fallbackTitle' => $chapter->getFallbackTitle(),
                'isEditable' => $course->canBeUpdated(),
            ],
        ]));
    }

    public function update(Request $request, Course $course)
    {
        $request->validate([
            'title' => ['nullable', 'string', 'max:191'],
        ]);

        $request->route('chapter')->update([
            'title' => $request->input('title'),
        ]);

        return back();
    }

    public function destroy(Request $request, Course $course)
    {
        $request->route('chapter')->delete();

        return to_route(CourseEditorContext::forRequest($request)->routeName('courses.content'), $course);
    }
}
