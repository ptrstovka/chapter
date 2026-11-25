<?php

namespace App\Support;

use App\Enums\CourseStatus;
use App\Enums\VideoProcessingStatus;
use App\Jobs\CalculateCourseDuration;
use App\Models\CompletedLesson;
use App\Models\Course;
use App\Models\CourseDraft;
use App\Models\Lesson;
use App\Models\Resource;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CoursePublication
{
    public function __construct(private CourseDrafts $drafts) {}

    public function problem(Course $course): ?string
    {
        $content = $course->editorContent();
        if (blank($content->title) || ! $content->category_id || ! $content->lessons()->exists()) {
            return __('Enter a title, select a category, and add at least one lesson before publishing.');
        }

        if ($course->videoUploads()->pending()->exists()) {
            return __('Wait for the video uploads to finish before publishing.');
        }

        $videos = $content->lessons()->whereNotNull('video_id')->with('video')
            ->get(['video_id'])->pluck('video')->push($content->trailer)->filter();
        if ($videos->contains(fn (Video $video) => $video->processing_status === VideoProcessingStatus::Failed)) {
            return __('Retry or remove the failed videos before publishing.');
        }

        if ($videos->contains(fn (Video $video) => ! $video->isReady())) {
            return __('Wait for the videos to finish processing before publishing.');
        }

        return null;
    }

    /** Apply the entire draft within the transaction that holds the course lock. */
    public function publish(Course $course): void
    {
        if ($problem = $this->problem($course)) {
            throw ValidationException::withMessages(['publication' => $problem]);
        }

        if ($draft = $course->draft) {
            $this->promote($course, $draft);
        } else {
            $course->sortLessonsWithinCourse();
            (new CalculateCourseDuration($course))->handle();
            $this->refreshProgress($course, $course->lessons->count());
            $course->increment('studio_revision');
        }

        $course->update(['status' => CourseStatus::Published, 'failure_reason' => null, 'has_been_published' => true]);
    }

    private function promote(Course $course, CourseDraft $draft): void
    {
        $course->load(['lessons', 'resources']);
        $draft->load(['chapters.lessons.resources', 'chapters.lessons.video', 'resources']);

        $draftLessons = $draft->chapters->flatMap->lessons;
        $draftVideos = $draftLessons->pluck('video_id')->push($draft->trailer_id)->filter();
        $releasedVideos = $course->lessons->pluck('video_id')->push($course->trailer_id)->filter()->unique()->diff($draftVideos);
        $previousCover = $course->cover_image_file_path;
        $oldLessons = $course->lessons->keyBy('uuid');

        $resources = $draft->resources->mapWithKeys(function (Resource $resource) use ($course) {
            $published = $course->resources()->updateOrCreate(
                ['uuid' => $resource->uuid],
                $resource->only(CourseDrafts::RESOURCE_FIELDS),
            );

            return [$resource->uuid => $published->id];
        });

        $position = 0;
        $hasNewLessons = false;
        foreach ($draft->chapters->sortBy('position') as $chapter) {
            $publishedChapter = $course->chapters()->updateOrCreate(
                ['uuid' => $chapter->uuid],
                $chapter->only(CourseDrafts::CHAPTER_FIELDS),
            );

            foreach ($chapter->lessons->sortBy('position') as $lesson) {
                $published = $oldLessons->pull($lesson->uuid) ?? $course->lessons()->make(['uuid' => $lesson->uuid]);
                $hasNewLessons = $hasNewLessons || ! $published->exists;
                $published->fill([
                    ...$lesson->only(CourseDrafts::LESSON_FIELDS),
                    'chapter_id' => $publishedChapter->id,
                    'position_within_course' => ++$position,
                ])->save();

                $published->resources()->sync($lesson->resources->map(fn (Resource $resource) => $resources[$resource->uuid]));
            }
        }

        $removedLessonIds = $oldLessons->pluck('id');
        if ($removedLessonIds->isNotEmpty()) {
            CompletedLesson::query()->whereIn('lesson_id', $removedLessonIds)->delete();
            $course->lessons()->whereIn('id', $removedLessonIds)->delete();
        }

        $course->chapters()->whereNotIn('uuid', $draft->chapters->pluck('uuid'))->delete();
        foreach ($course->resources->whereNotIn('uuid', $resources->keys()) as $resource) {
            $course->resources()->whereKey($resource->id)->delete();
            $this->drafts->deleteUnusedFile($resource->file_path);
        }

        $course->fill($draft->only(CourseDrafts::COURSE_FIELDS));
        $course->duration_seconds = $draftLessons->sum(fn (Lesson $lesson) => $lesson->video?->duration_seconds ?? 0);
        $course->save();

        if ($hasNewLessons || $removedLessonIds->isNotEmpty()) {
            $this->refreshProgress($course, $position);
        }

        $this->drafts->remove($course, promoted: true);
        if ($previousCover !== $course->cover_image_file_path) {
            $this->drafts->deleteUnusedFile($previousCover);
        }
        foreach ($releasedVideos as $videoId) {
            $this->drafts->deleteUnusedVideo($videoId);
        }
    }

    private function refreshProgress(Course $course, int $total): void
    {
        $completedCount = CompletedLesson::query()
            ->selectRaw('COUNT(*)')
            ->whereRelation('lesson', 'course_id', $course->id)
            ->whereColumn('completed_lessons.user_id', 'course_enrollments.user_id');

        $inProgress = $course->enrollments()->whereNull('completed_at');
        (clone $inProgress)->where($completedCount, '=', $total)->update(['progress' => 100, 'completed_at' => now()]);
        $inProgress->update([
            // Ceiling the percentage while reserving 100 for learners who completed every lesson.
            'progress' => DB::raw("CASE WHEN ({$completedCount->toRawSql()}) * 100 > ".(98 * $total).
                " THEN 99 ELSE CEIL(({$completedCount->toRawSql()}) * 100.0 / {$total}) END"),
        ]);
    }
}
