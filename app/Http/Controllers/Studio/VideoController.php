<?php

namespace App\Http\Controllers\Studio;

use App\Enums\CoursePreviewVersion;
use App\Enums\VideoUploadStatus;
use App\Models\Course;
use App\Models\CourseDraft;
use App\Models\Video;
use App\Support\CourseDrafts;
use App\Support\CoursePublication;
use App\View\Models\VideoSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VideoController
{
    public function index(Request $request, Course $course, CoursePublication $publication): JsonResponse
    {
        Gate::authorize('update', $course);
        $request->validate(['lesson_id' => ['nullable', 'uuid']]);
        $content = $course->editorContent();
        $lesson = $request->filled('lesson_id') ? $content->lessons()->where('uuid', $request->input('lesson_id'))->firstOrFail() : null;
        $problem = $publication->problem($course);

        return response()->json([
            'video' => VideoSource::for($lesson ? $lesson->video : $content->trailer)?->toView(),
            'canBePublished' => $problem === null,
            'publicationProblem' => $problem,
            'hasUnpublishedChanges' => $course->draft?->has_changes ?? false,
            'hasCourseDraft' => $course->draft !== null,
            'courseStatus' => $course->status,
            'revision' => $course->studio_revision,
            'previewOptions' => CoursePreviewVersion::optionsFor($course),
        ]);
    }

    public function destroy(Request $request, Course $course, Video $video, CourseDrafts $drafts): Response|JsonResponse
    {
        Gate::authorize('update', $course);
        $request->validate(['lesson_id' => ['nullable', 'uuid']]);

        $editorRevision = DB::transaction(function () use ($request, $course, $video, $drafts) {
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);
            abort_unless($course->canBeUpdated(), 409);
            if ($course->hasPublication()) {
                $drafts->verifyRevision($request, $course);
            }

            $content = $drafts->edit($course);
            $lesson = null;
            if (! $request->has('lesson_id') || $request->filled('lesson_id')) {
                $lesson = $content->lessons()->where('video_id', $video->id)
                    ->when($request->filled('lesson_id'), fn ($query) => $query->where('uuid', $request->input('lesson_id')))
                    ->first();
            }
            abort_unless($lesson || (! $request->filled('lesson_id') && $content->trailer_id === $video->id), 404);

            $course->videoUploads()->where([
                'course_draft_id' => $content instanceof CourseDraft ? $content->id : null,
                'lesson_id' => $content instanceof CourseDraft ? null : $lesson?->id,
                'draft_lesson_id' => $content instanceof CourseDraft ? $lesson?->id : null,
            ])->pending()->update(['status' => VideoUploadStatus::Cancelled]);

            if ($lesson) {
                $lesson->video()->disassociate()->save();
            } else {
                $content->trailer()->disassociate()->save();
            }
            $drafts->deleteUnusedVideo($video->id);

            if ($content instanceof CourseDraft) {
                $before = $course->studio_revision;
                $drafts->changed($course, $content);

                return ['before' => $before, 'after' => $course->studio_revision];
            }

            return null;
        });

        return $editorRevision ? response()->json(['editorRevision' => $editorRevision]) : response()->noContent();
    }
}
