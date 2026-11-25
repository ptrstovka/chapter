<?php

namespace App\Http\Controllers\Studio;

use App\Models\Chapter;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SortChaptersController
{
    public function __invoke(Request $request, Course $course)
    {
        $chapters = $course->editorContent()->chapters;
        $request->validate([
            'chapters' => ['required', 'array', 'size:'.$chapters->count()],
            'chapters.*' => ['required', 'string', 'distinct', Rule::in($chapters->pluck('uuid'))],
        ]);

        $positions = array_flip($request->array('chapters'));

        DB::transaction(fn () => $chapters
            ->sortBy(fn (Chapter $chapter) => $positions[$chapter->uuid])
            ->values()
            ->each(fn (Chapter $chapter, int $idx) => $chapter->update(['position' => $idx + 1]))
        );

        return back();
    }
}
