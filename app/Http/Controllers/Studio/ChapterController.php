<?php

namespace App\Http\Controllers\Studio;

use App\Models\Chapter;
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
            $chapter = $course->chapters()->create([
                'position' => ($course->chapters()->max('position') ?: 0) + 1,
            ]);

            if ($request->has('lesson')) {
                $lesson = new Lesson([
                    'position' => 1,
                    'description_type' => $course->author->getDefaultTextContentType(),
                    'position_within_course' => 0,
                ]);
                $lesson->course()->associate($course);
                $lesson->chapter()->associate($chapter);
                $lesson->save();

                return $lesson;
            }

            return $chapter;
        });

        if ($lessonOrChapter instanceof Chapter) {
            return to_route('studio.course.chapters.show', [$course, $lessonOrChapter]);
        } elseif ($lessonOrChapter instanceof Lesson) {
            return to_route('studio.course.lessons.show', [$course, $lessonOrChapter]);
        }

        return back();
    }

    public function show(Course $course, Chapter $chapter)
    {
        return Inertia::render('Studio/CourseContentChapter', CourseContentLayout::make($course, [
            'chapter' => [
                'id' => $chapter->uuid,
                'title' => $chapter->title,
                'fallbackTitle' => $chapter->getFallbackTitle(),
                'isEditable' => $course->canBeUpdated(),
            ],
        ]));
    }

    public function update(Request $request, Course $course, Chapter $chapter)
    {
        $request->validate([
            'title' => ['nullable', 'string', 'max:191'],
        ]);

        $chapter->update([
            'title' => $request->input('title'),
        ]);

        return back();
    }

    public function destroy(Course $course, Chapter $chapter)
    {
        DB::transaction(fn () => $chapter->delete());

        return to_route('studio.courses.content', $course);
    }
}
