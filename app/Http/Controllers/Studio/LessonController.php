<?php


namespace App\Http\Controllers\Studio;


use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\View\Layouts\CourseContentLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LessonController
{
    public function store(Course $course, Chapter $chapter)
    {
        $position = ($chapter->lessons()->max('position') ?: 0) + 1;

        $lesson = new Lesson([
            'position' => $position,
        ]);
        $lesson->chapter()->associate($chapter);
        $lesson->course()->associate($course);
        $lesson->save();

        return back();
    }

    public function show(Course $course, Chapter $chapter, Lesson $lesson)
    {
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
            ],
        ]));
    }

    public function update(Request $request, Course $course, Chapter $chapter, Lesson $lesson)
    {
        $request->validate([
            'title' => ['nullable', 'string', 'max:191'],
        ]);

        if ($title = $request->input('title')) {
            $lesson->title = $title;
            $lesson->slug = Str::slug($title);
        } else {
            $lesson->title = null;
            $lesson->slug = null;
        }

        $lesson->save();

        return back();
    }

    public function destroy(Course $course, Chapter $chapter, Lesson $lesson)
    {
        DB::transaction(fn () => $lesson->delete());

        return to_route('studio.course.chapters.show', [$course, $chapter]);
    }
}
