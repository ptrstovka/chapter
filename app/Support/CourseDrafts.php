<?php

namespace App\Support;

use App\Enums\VideoUploadStatus;
use App\Models\Course;
use App\Models\CourseDraft;
use App\Models\CourseDraftLesson;
use App\Models\CourseDraftResource;
use App\Models\Lesson;
use App\Models\Resource;
use App\Models\TemporaryUpload;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CourseDrafts
{
    public const array COURSE_FIELDS = ['title', 'description', 'description_type', 'category_id', 'cover_image_file_path', 'trailer_id'];

    public const array CHAPTER_FIELDS = ['title', 'position'];

    public const array LESSON_FIELDS = ['title', 'slug', 'description', 'description_type', 'video_id', 'position'];

    public const array RESOURCE_FIELDS = ['file_path', 'client_file_name', 'size', 'mime_type'];

    public function verifyRevision(Request $request, Course $course): void
    {
        $request->validate(['revision' => ['required', 'integer', 'min:0']]);
        if ($request->integer('revision') !== $course->studio_revision) {
            $message = __('This course was changed in another editor. Reload the course to continue.');
            abort_if($request->expectsJson(), 409, $message);

            throw ValidationException::withMessages(['revision' => $message]);
        }
    }

    /** Call inside the transaction that holds the course lock. */
    public function edit(Course $course): Course|CourseDraft
    {
        if (! $course->hasPublication()) {
            return $course;
        }

        if ($course->draft) {
            return $course->draft;
        }

        $course->load(['chapters.lessons.resources', 'resources']);
        $draft = $course->draft()->create($course->only(self::COURSE_FIELDS));
        $course->setRelation('draft', $draft);

        $resourceIds = $course->resources->mapWithKeys(function (Resource $resource) use ($draft) {
            $copy = $draft->resources()->create(['uuid' => $resource->uuid, ...$resource->only(self::RESOURCE_FIELDS)]);

            return [$resource->id => $copy->id];
        });

        foreach ($course->chapters as $chapter) {
            $draftChapter = $draft->chapters()->create(['uuid' => $chapter->uuid, ...$chapter->only(self::CHAPTER_FIELDS)]);
            foreach ($chapter->lessons as $lesson) {
                $draftLesson = $draft->lessons()->create([
                    'uuid' => $lesson->uuid,
                    'chapter_id' => $draftChapter->id,
                    ...$lesson->only(self::LESSON_FIELDS),
                ]);
                $draftLesson->resources()->attach($lesson->resources->map(fn (Resource $resource) => $resourceIds[$resource->id]));
            }
        }

        return $draft;
    }

    public function changed(Course $course, CourseDraft $draft): void
    {
        $draft->update(['has_changes' => true]);
        $course->increment('studio_revision');
    }

    /** Discard or release a draft while holding the course lock. */
    public function remove(Course $course, bool $promoted = false): void
    {
        $draft = $course->draft;
        if (! $draft) {
            return;
        }

        $draftVideos = $promoted ? collect() : $draft->lessons()->pluck('video_id')->push($draft->trailer_id)->filter()->unique();
        $draftFiles = $promoted ? collect() : $draft->resources()->pluck('file_path')->push($draft->cover_image_file_path)->filter()->unique();

        $course->videoUploads()->where('course_draft_id', $draft->id)
            ->where('status', VideoUploadStatus::Pending)
            ->update(['status' => VideoUploadStatus::Cancelled]);

        $draft->delete();
        $course->setRelation('draft', null);
        $course->increment('studio_revision');

        foreach ($draftVideos as $videoId) {
            $this->deleteUnusedVideo($videoId);
        }
        foreach ($draftFiles as $path) {
            $this->deleteUnusedFile($path);
        }
    }

    public function copyUpload(TemporaryUpload $upload, string $directory): string
    {
        $path = $upload->copyToContentDisk($directory);
        DB::afterRollBack(fn () => Storage::disk(App::contentDisk())->delete($path));
        if (! Storage::disk(App::contentDisk())->exists($path)) {
            throw new RuntimeException('Unable to store the course file.');
        }

        return $path;
    }

    public function deleteUnusedFile(?string $path): void
    {
        if ($path === null) {
            return;
        }

        DB::afterCommit(function () use ($path) {
            try {
                if (Course::withTrashed()->where('cover_image_file_path', $path)->exists()
                    || CourseDraft::query()->where('cover_image_file_path', $path)->exists()
                    || Resource::query()->where('file_path', $path)->exists()
                    || CourseDraftResource::query()->where('file_path', $path)->exists()) {
                    return;
                }

                Storage::disk(App::contentDisk())->delete($path);
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }

    public function deleteUnusedVideo(?int $videoId): void
    {
        if ($videoId === null) {
            return;
        }

        DB::afterCommit(function () use ($videoId) {
            try {
                if (Course::withTrashed()->where('trailer_id', $videoId)->exists()
                    || CourseDraft::query()->where('trailer_id', $videoId)->exists()
                    || Lesson::query()->where('video_id', $videoId)->exists()
                    || CourseDraftLesson::query()->where('video_id', $videoId)->exists()) {
                    return;
                }

                Video::find($videoId)?->delete();
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }
}
