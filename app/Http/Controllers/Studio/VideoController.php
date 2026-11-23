<?php

namespace App\Http\Controllers\Studio;

use App\Enums\VideoUploadStatus;
use App\Models\Course;
use App\Models\Video;
use App\View\Models\VideoSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VideoController
{
    public function index(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);
        $request->validate(['lesson_id' => ['nullable', 'uuid']]);
        $lesson = $request->filled('lesson_id') ? $course->lessons()->where('uuid', $request->input('lesson_id'))->firstOrFail() : null;

        return response()->json([
            'video' => VideoSource::for($lesson ? $lesson->video : $course->trailer)?->toView(),
            'canBePublished' => $course->canBePublished(),
            'courseStatus' => $course->status,
        ]);
    }

    public function destroy(Course $course, Video $video): Response
    {
        Gate::authorize('update', $course);
        DB::transaction(function () use ($course, $video) {
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);
            abort_unless($course->canBeUpdated(), 409);
            $lesson = $course->lessons()->where('video_id', $video->id)->first();
            abort_unless($lesson || $course->trailer_id === $video->id, 404);
            $course->videoUploads()->pending()->where('lesson_id', $lesson?->id)->update(['status' => VideoUploadStatus::Cancelled]);
            if ($lesson) {
                $lesson->video()->disassociate()->save();
            } else {
                $course->trailer()->disassociate()->save();
            }
            $video->delete();
        });

        return response()->noContent();
    }
}
