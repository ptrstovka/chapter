<?php


namespace App\Http\Controllers\Studio;


use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MoveLessonController
{
    public function __invoke(Request $request, Course $course)
    {
        $chapters = $course->chapters->pluck('uuid');

        $request->validate([
            'source_chapter' => ['required', 'string', Rule::in($chapters)],
            'destination_chapter' => ['required', 'string', Rule::in($chapters)],
        ]);

        $sourceChapter = $course->chapters->firstOrFail('uuid', '=', $request->input('source_chapter'));
        $destinationChapter = $course->chapters->firstOrFail('uuid', '=', $request->input('destination_chapter'));

        $request->validate([
            'lesson' => ['required', 'string', Rule::in($sourceChapter->lessons->pluck('uuid'))],
            'destination_index' => ['required', 'integer', 'min:0'],
        ]);

        $lesson = $sourceChapter->lessons->firstOrFail('uuid', '=', $request->input('lesson'));
        $destinationIndex = $request->input('destination_index');

        if ($sourceChapter->is($destinationChapter)) {
            $lessons = $sourceChapter->lessons->sortBy('position')->values();
            $startIndex = $lessons->search($lesson);

            DB::transaction(fn () => collect(Arr::move($lessons->all(), $startIndex, $destinationIndex))
                ->each(fn (Lesson $lesson, int $idx) => $lesson->update([
                    'position' => $idx + 1,
                ]))
            );
        } else {
            $lessons = collect(Arr::insert($destinationChapter->lessons->sortBy('position')->values()->all(), $lesson, $destinationIndex));

            DB::transaction(function () use ($lessons, $sourceChapter, $destinationChapter) {
                $lessons->each(fn (Lesson $lesson, int $idx) => $lesson->update([
                    'position' => $idx + 1,
                    'chapter_id' => $destinationChapter->id,
                ]));

                $sourceChapter->load('lessons');

                $sourceChapter->lessons->sortBy('position')->values()->each(fn (Lesson $lesson, int $idx) => $lesson->update([
                    'position' => $idx + 1,
                ]));
            });
        }

        return back();
    }
}
