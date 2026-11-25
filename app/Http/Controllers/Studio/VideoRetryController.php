<?php

namespace App\Http\Controllers\Studio;

use App\Enums\VideoProcessingStatus;
use App\Models\Course;
use App\Models\Video;
use App\Support\VideoProcessing;
use App\Support\VideoSettings;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VideoRetryController
{
    public function __invoke(Course $course, Video $video, VideoProcessing $processing, VideoSettings $settings): Response
    {
        Gate::authorize('update', $course);
        DB::transaction(function () use ($course, $video, $processing, $settings) {
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);
            abort_unless($course->canBeUpdated(), 409);
            $content = $course->editorContent();
            abort_unless($content->trailer_id === $video->id || $content->lessons()->where('video_id', $video->id)->exists(), 404);
            abort_if($course->hasPublication() && ($course->trailer_id === $video->id
                || $course->lessons()->where('video_id', $video->id)->exists()), 409);
            abort_unless($video->processing_status === VideoProcessingStatus::Failed && $video->file_path, 409);
            $processing->dispatch($video, $processing->prepare($video, $settings->snapshot(), retry: true));
        });

        return response()->noContent();
    }
}
