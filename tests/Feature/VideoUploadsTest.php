<?php

use App\Enums\CourseStatus;
use App\Enums\VideoUploadStatus;
use App\Jobs\DeleteVideoFiles;
use App\Jobs\ProcessVideo;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
    config(['filesystems.content_disk' => 'public']);
    $this->creator = User::factory()->author()->create();
    $this->course = Course::factory()->for($this->creator->author)->for(Category::factory())->create(['status' => CourseStatus::Draft]);
    $chapter = Chapter::factory()->for($this->course)->create();
    $this->lesson = Lesson::factory()->for($this->course)->for($chapter)->create();
    $this->actingAs($this->creator);
    Queue::fake();
});

function registerTestVideoUpload($test, ?string $uuid = null): array
{
    return $test->postJson(route('studio.video-uploads.store', $test->course), [
        'uuid' => $uuid ?? (string) Str::uuid(), 'lesson_id' => $test->lesson->uuid,
        'filename' => 'clip.mp4', 'size' => 12,
    ])->assertCreated()->json();
}

it('uploads and attaches a video immediately and completes idempotently', function () {
    $upload = registerTestVideoUpload($this);
    expect($this->course->canBePublished())->toBeFalse();
    $this->postJson(route('files.store'), [
        'scope' => 'CourseVideo', 'video_upload_id' => $upload['id'],
        'file' => UploadedFile::fake()->createWithContent('clip.mp4', 'source video'),
    ])->assertSuccessful()->assertJsonPath('received', true);
    $this->getJson(route('studio.video-uploads.show', $upload['id']))->assertJsonPath('received', true);
    $this->postJson(route('studio.video-uploads.complete', $upload['id']))->assertSuccessful()->assertJsonPath('status', 'completed');
    $video = $this->lesson->fresh()->video;
    expect($video)->not->toBeNull()->and($video->processing_options['hls'])->toBeFalse()
        ->and($video->original_size_bytes)->toBe(12);
    Storage::disk('public')->assertExists($video->file_path);
    $this->postJson(route('studio.video-uploads.complete', $upload['id']))->assertSuccessful()->assertJsonPath('videoId', $video->id);
    expect(Video::count())->toBe(1);
    Queue::assertPushed(ProcessVideo::class, 1);

    VideoUpload::query()->delete();
    expect($video->fresh()->original_size_bytes)->toBe(12);
});

it('accepts the maximum video size for registration and upload', function (bool $lesson, ?int $limitMb) {
    if ($limitMb !== null) {
        config(['video.max_upload_size_mb' => $limitMb]);
    }
    $size = ($limitMb ?? 5120) * 1024 ** 2;
    $upload = $this->postJson(route('studio.video-uploads.store', $this->course), [
        'uuid' => (string) Str::uuid(),
        'lesson_id' => $lesson ? $this->lesson->uuid : null,
        'filename' => 'clip.mp4',
        'size' => $size,
    ])->assertCreated()->assertJsonPath('size', $size)->json();
    $file = UploadedFile::fake()->createWithContent('clip.mp4', 'source video');
    $file->sizeToReport = $size;

    $this->postJson(route('files.store'), [
        'scope' => $lesson ? 'CourseVideo' : 'CourseTrailerVideo',
        'video_upload_id' => $upload['id'],
        'file' => $file,
    ])->assertSuccessful()->assertJsonPath('received', true);

    expect(VideoUpload::findByUuid($upload['id'])->temporaryUpload->size)->toBe($size);
})->with(['lesson' => true, 'trailer' => false])->with(['default 5 GB' => null, 'custom 64 MB' => 64]);

it('rejects videos one byte above the configured limit at registration and upload', function (bool $lesson, int $limitMb) {
    config(['video.max_upload_size_mb' => $limitMb]);
    $size = $limitMb * 1024 ** 2;
    $payload = [
        'uuid' => (string) Str::uuid(),
        'lesson_id' => $lesson ? $this->lesson->uuid : null,
        'filename' => 'clip.mp4',
        'size' => $size + 1,
    ];
    $this->postJson(route('studio.video-uploads.store', $this->course), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('size');
    expect(VideoUpload::count())->toBe(0);

    $upload = $this->postJson(route('studio.video-uploads.store', $this->course), [...$payload, 'size' => $size])
        ->assertCreated()->json();
    $file = UploadedFile::fake()->createWithContent('clip.mp4', 'source video');
    $file->sizeToReport = $size + 1;

    $this->postJson(route('files.store'), [
        'scope' => $lesson ? 'CourseVideo' : 'CourseTrailerVideo',
        'video_upload_id' => $upload['id'],
        'file' => $file,
    ])->assertUnprocessable()->assertJsonValidationErrors('file');

    expect(VideoUpload::findByUuid($upload['id'])->temporary_upload_id)->toBeNull();
    Storage::disk('public')->assertDirectoryEmpty('/');
})->with(['lesson' => true, 'trailer' => false])->with(['5 GB' => 5120, 'custom 64 MB' => 64]);

it('shares the configured video limit with the browser', function () {
    config(['video.max_upload_size_mb' => 3072]);

    $response = $this->get(route('studio.courses.show', $this->course))->assertSuccessful();

    expect($response->inertiaProps('app.videoUploadMaxSize'))->toBe(3 * 1024 ** 3)
        ->and($response->inertiaProps('app.videoUploadMaxSizeLabel'))->toBe('3 GB');
});

it('keeps the attachment limit at two gigabytes', function () {
    $file = UploadedFile::fake()->createWithContent('resource.pdf', 'resource');
    $file->sizeToReport = 2 * 1024 ** 3 + 1;

    $this->postJson(route('files.store'), ['scope' => 'CourseResource', 'file' => $file])
        ->assertUnprocessable()->assertJsonValidationErrors('file');
    Storage::disk('public')->assertDirectoryEmpty('/');
});

it('keeps the current video until replacement completion and rejects a cancelled receipt', function () {
    $original = Video::factory()->ready()->create();
    $this->lesson->video()->associate($original)->save();
    $upload = registerTestVideoUpload($this);
    expect($this->lesson->fresh()->video_id)->toBe($original->id);
    $this->deleteJson(route('studio.video-uploads.destroy', [$this->course, $upload['id']]), ['lesson_id' => $this->lesson->uuid, 'filename' => 'clip.mp4', 'size' => 12])->assertSuccessful();
    $this->postJson(route('studio.video-uploads.complete', $upload['id']))->assertConflict();
    $this->postJson(route('files.store'), [
        'scope' => 'CourseVideo', 'video_upload_id' => $upload['id'],
        'file' => UploadedFile::fake()->createWithContent('clip.mp4', 'source video'),
    ])->assertConflict();
    expect($this->lesson->fresh()->video_id)->toBe($original->id);
});

it('supersedes old sessions and reuses registration UUIDs without creating duplicates', function () {
    $uuid = (string) Str::uuid();
    $first = registerTestVideoUpload($this, $uuid);
    registerTestVideoUpload($this, $uuid);
    expect(VideoUpload::count())->toBe(1);
    registerTestVideoUpload($this);
    expect(VideoUpload::findByUuid($first['id'])->status)->toBe(VideoUploadStatus::Cancelled);
});

it('keeps an upload cancelled when its registration arrives after cancellation', function () {
    $uuid = (string) Str::uuid();
    $this->deleteJson(route('studio.video-uploads.destroy', [$this->course, $uuid]), [
        'lesson_id' => $this->lesson->uuid, 'filename' => 'clip.mp4', 'size' => 12,
    ])->assertSuccessful()->assertJsonPath('status', 'cancelled');

    expect(registerTestVideoUpload($this, $uuid)['status'])->toBe('cancelled')
        ->and(VideoUpload::count())->toBe(1)
        ->and($this->course->videoUploads()->pending()->exists())->toBeFalse();
    $this->postJson(route('studio.video-uploads.complete', $uuid))->assertConflict();
    Queue::assertNothingPushed();
});

it('rejects another authors course, lesson and session', function () {
    $upload = registerTestVideoUpload($this);
    $other = User::factory()->author()->create();
    $this->actingAs($other)->getJson(route('studio.video-uploads.show', $upload['id']))->assertForbidden();
    $this->postJson(route('studio.video-uploads.store', $this->course), [
        'uuid' => (string) Str::uuid(), 'filename' => 'clip.mp4', 'size' => 12,
    ])->assertForbidden();

    $otherCourse = Course::factory()->for($other->author)->for(Category::factory())->create(['status' => CourseStatus::Draft]);
    $this->postJson(route('studio.video-uploads.store', $otherCourse), [
        'uuid' => (string) Str::uuid(), 'lesson_id' => $this->lesson->uuid, 'filename' => 'clip.mp4', 'size' => 12,
    ])->assertNotFound();
});

it('prevents upload registration and content changes while publishing', function () {
    $this->course->update(['status' => CourseStatus::Publishing]);
    $this->postJson(route('studio.video-uploads.store', $this->course), [
        'uuid' => (string) Str::uuid(), 'filename' => 'clip.mp4', 'size' => 12,
    ])->assertConflict();
    $this->deleteJson(route('studio.course.lessons.destroy', [$this->course, $this->lesson->chapter, $this->lesson]))->assertConflict();
});

it('does not let saving lesson text overwrite an automatically assigned video', function () {
    $video = Video::factory()->ready()->create();
    $this->lesson->video()->associate($video)->save();
    $this->patch(route('studio.course.lessons.update', [$this->course, $this->lesson->chapter, $this->lesson]), [
        'title' => 'Edited title', 'description_type' => 'html', 'remove_video' => true, 'resources' => [],
    ])->assertRedirect();
    expect($this->lesson->fresh()->video_id)->toBe($video->id)->and($this->lesson->fresh()->title)->toBe('Edited title');
});

it('dispatches replacement processing even if queuing the old video cleanup fails', function () {
    $original = Video::factory()->ready()->create();
    Storage::disk('public')->put($original->file_path, 'old source');
    $this->lesson->video()->associate($original)->save();
    $upload = registerTestVideoUpload($this);
    $this->postJson(route('files.store'), [
        'scope' => 'CourseVideo', 'video_upload_id' => $upload['id'],
        'file' => UploadedFile::fake()->createWithContent('clip.mp4', 'source video'),
    ])->assertSuccessful();
    Bus::shouldReceive('dispatch')->once()->with(Mockery::type(DeleteVideoFiles::class))
        ->andThrow(new RuntimeException('Cleanup queue unavailable'));
    Bus::shouldReceive('dispatch')->once()->with(Mockery::type(ProcessVideo::class))->andReturnNull();

    $this->postJson(route('studio.video-uploads.complete', $upload['id']))->assertSuccessful()->assertJsonPath('status', 'completed');

    expect($this->lesson->fresh()->video_id)->not->toBe($original->id);
    Storage::disk('public')->assertMissing($original->file_path);
});
