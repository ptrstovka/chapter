<?php

namespace App\Support;

use App\Enums\VideoUploadStatus;
use App\Models\Course;
use App\Models\CourseDraft;
use App\Models\TemporaryUpload;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class VideoUploads
{
    public function __construct(protected VideoProcessing $processing, protected VideoSettings $settings, protected CourseDrafts $drafts) {}

    public function authorize(VideoUpload $upload, User $user): void
    {
        abort_unless($upload->user_id === $user->id, 403);
        abort_unless($upload->course, 404);
        Gate::authorize('update', $upload->course);
    }

    public function register(Course $course, ?string $lessonUuid, User $user, string $uuid, string $filename, int $size, ?int $revision): VideoUpload
    {
        Gate::authorize('update', $course);

        return DB::transaction(function () use ($course, $lessonUuid, $user, $uuid, $filename, $size, $revision) {
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);
            abort_unless($course->canBeUpdated(), 409, __('This course cannot be edited.'));

            if ($existing = VideoUpload::query()->where('uuid', $uuid)->first()) {
                abort_unless($existing->user_id === $user->id && $existing->course_id === $course->id
                    && $existing->filename === $filename && $existing->size === $size, 409);
                abort_unless($existing->lessonUuid() === $lessonUuid, 409);

                return $existing;
            }

            abort_if($course->hasPublication() && $revision !== $course->studio_revision, 409, __('This course was changed in another editor. Reload the course to continue.'));
            $content = $this->drafts->edit($course);
            $lesson = $lessonUuid ? $content->lessons()->where('uuid', $lessonUuid)->firstOrFail() : null;
            $target = [
                'course_id' => $course->id,
                'course_draft_id' => $content instanceof CourseDraft ? $content->id : null,
                'lesson_id' => $content instanceof CourseDraft ? null : $lesson?->id,
                'draft_lesson_id' => $content instanceof CourseDraft ? $lesson?->id : null,
            ];

            VideoUpload::query()->where($target)->pending()->update(['status' => VideoUploadStatus::Cancelled]);

            return VideoUpload::create([
                ...$target,
                'lesson_uuid' => $lessonUuid,
                'uuid' => $uuid, 'user_id' => $user->id,
                'filename' => $filename, 'size' => $size,
                'expires_at' => now()->addHours(config('video.upload_retention_hours')),
            ]);
        });
    }

    public function receive(VideoUpload $upload, UploadedFile $file): VideoUpload
    {
        return Cache::lock('video-upload:'.$upload->uuid, (int) config('video.timeout'))->block(1, function () use ($upload, $file) {
            $upload->refresh();
            $this->assertPending($upload);
            if ($upload->temporary_upload_id) {
                return $upload;
            }
            abort_unless($file->getSize() === $upload->size, 422, __('The uploaded file size does not match.'));
            $disk = TemporaryUpload::disk($upload->uploadScope());
            $path = TemporaryUpload::dir($upload->uploadScope()).'/'.Str::random(32).'.'.$file->extension();
            try {
                $stream = fopen($file->getRealPath(), 'rb');
                try {
                    if (! is_resource($stream) || ! Storage::disk($disk)->writeStream($path, $stream)) {
                        throw new RuntimeException('Unable to store the uploaded video.');
                    }
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                return DB::transaction(function () use ($upload, $file, $disk, $path) {
                    $course = Course::query()->lockForUpdate()->findOrFail($upload->course_id);
                    $upload->refresh();
                    $this->assertPending($upload);
                    $this->assertEditableTarget($course, $upload);
                    $temporary = TemporaryUpload::create([
                        'user_id' => $upload->user_id, 'disk' => $disk, 'path' => $path,
                        'scope' => $upload->uploadScope(), 'mime_type' => $file->getMimeType(),
                        'client_file_name' => $upload->filename, 'size' => $file->getSize(),
                    ]);
                    $upload->update(['temporary_upload_id' => $temporary->id, 'expires_at' => now()->addHours(config('video.upload_retention_hours'))]);

                    return $upload;
                });
            } catch (Throwable $exception) {
                Storage::disk($disk)->delete($path);
                throw $exception;
            }
        });
    }

    public function complete(VideoUpload $upload): VideoUpload
    {
        return Cache::lock('video-upload:'.$upload->uuid, (int) config('video.timeout'))->block(1, function () use ($upload) {
            $upload->refresh();
            if ($upload->status === VideoUploadStatus::Completed) {
                return $upload;
            }
            $this->assertPending($upload);
            $temporary = $upload->temporaryUpload;
            abort_unless($temporary && $temporary->user_id === $upload->user_id && $temporary->scope === $upload->uploadScope(), 409);
            $destination = 'course-videos/'.$upload->uuid.'.'.strtolower(pathinfo($temporary->path, PATHINFO_EXTENSION));
            $disk = Storage::disk(App::contentDisk());

            try {
                $this->processing->copy(Storage::disk($temporary->disk), $temporary->path, $disk, $destination);

                return DB::transaction(function () use ($upload, $temporary, $destination) {
                    $course = Course::query()->lockForUpdate()->findOrFail($upload->course_id);
                    $upload->refresh();
                    $this->assertPending($upload);
                    $content = $this->assertEditableTarget($course, $upload);
                    $lessonId = $upload->draft_lesson_id ?? $upload->lesson_id;
                    $lesson = $lessonId ? $content->lessons()->findOrFail($lessonId) : null;
                    $previous = $lesson ? $lesson->video : $content->trailer;
                    $video = Video::create(['file_path' => $destination, 'original_size_bytes' => $temporary->size]);
                    $attempt = $this->processing->prepare($video, $this->settings->snapshot());
                    if ($lesson) {
                        $lesson->video()->associate($video);
                        $lesson->save();
                    } else {
                        $content->trailer()->associate($video)->save();
                    }
                    $upload->update(['video_id' => $video->id, 'status' => VideoUploadStatus::Completed]);
                    if ($content instanceof CourseDraft) {
                        $this->drafts->changed($course, $content);
                        $upload->update(['editor_revision' => $course->studio_revision]);
                    }
                    $this->drafts->deleteUnusedVideo($previous?->id);
                    $temporary->delete();
                    $this->processing->dispatch($video, $attempt);

                    return $upload;
                });
            } catch (Throwable $exception) {
                if (! Video::query()->where('file_path', $destination)->exists()) {
                    $disk->delete($destination);
                }
                throw $exception;
            }
        });
    }

    public function cancel(Course $course, ?string $lessonUuid, User $user, string $uuid, string $filename, int $size): VideoUpload
    {
        Gate::authorize('update', $course);

        return DB::transaction(function () use ($course, $lessonUuid, $user, $uuid, $filename, $size) {
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);
            $upload = VideoUpload::query()->where('uuid', $uuid)->first();
            if (! $upload) {
                $content = $course->editorContent();
                $lesson = $lessonUuid ? $content->lessons()->where('uuid', $lessonUuid)->firstOrFail() : null;

                return VideoUpload::create([
                    'uuid' => $uuid, 'user_id' => $user->id, 'course_id' => $course->id,
                    'course_draft_id' => $content instanceof CourseDraft ? $content->id : null,
                    'lesson_id' => $content instanceof CourseDraft ? null : $lesson?->id,
                    'draft_lesson_id' => $content instanceof CourseDraft ? $lesson?->id : null,
                    'lesson_uuid' => $lessonUuid,
                    'filename' => $filename, 'size' => $size, 'status' => VideoUploadStatus::Cancelled,
                    'expires_at' => now()->addHours(config('video.upload_retention_hours')),
                ]);
            }
            abort_unless($upload->user_id === $user->id && $upload->course_id === $course->id, 403);
            if ($upload->status === VideoUploadStatus::Pending) {
                $upload->update(['status' => VideoUploadStatus::Cancelled]);
            }

            return $upload;
        });
    }

    protected function assertPending(VideoUpload $upload): void
    {
        abort_unless($upload->status === VideoUploadStatus::Pending && $upload->expires_at->isFuture(), 409, __('This upload is no longer active.'));
    }

    private function assertEditableTarget(Course $course, VideoUpload $upload): Course|CourseDraft
    {
        abort_unless($course->canBeUpdated(), 409);
        if (! $course->hasPublication()) {
            return $course;
        }

        $draft = $course->draft;
        abort_unless($draft && $draft->id === $upload->course_draft_id, 409, __('This upload is no longer active.'));

        return $draft;
    }
}
