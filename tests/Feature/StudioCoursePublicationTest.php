<?php

use App\Enums\CourseAccessMode;
use App\Enums\CourseStatus;
use App\Enums\VideoProcessingStatus;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Resource;
use App\Models\TemporaryUpload;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->author = User::factory()->author()->create();
    $this->learner = User::factory()->create();
    $this->course = Course::factory()->for($this->author->author)->for(Category::factory())->create([
        'title' => 'Original physics course',
        'slug' => 'original-physics',
    ]);
    $this->chapter = Chapter::factory()->for($this->course)->create(['title' => 'Original chapter']);
    $this->lesson = Lesson::factory()->for($this->course)->for($this->chapter)->create(['title' => 'Original lesson']);
    $this->learner->enrolledCourses()->create(['course_id' => $this->course->id, 'progress' => 0, 'started_at' => now()]);
    $this->actingAs($this->author);
});

it('keeps learner pages and search on the published course until the author publishes the draft', function () {
    $courseUrl = route('courses.show', $this->course->slug);
    $newCategory = Category::factory()->create();
    $this->patch(route('studio.courses.update', $this->course), [
        'revision' => 0, 'title' => 'Revised astronomy course', 'category' => $newCategory->id,
        'slug' => $this->course->slug, 'description' => '<p>New description</p>', 'description_type' => 'html',
    ])->assertSessionHasNoErrors();

    $this->get(route('studio.courses.show', $this->course))->assertInertia(fn (Assert $page) => $page
        ->where('title', 'Revised astronomy course')->where('slugLocked', true)
        ->where('hasUnpublishedChanges', true)->where('status', 'published')->where('revision', 1));
    $this->actingAs($this->learner)->get($courseUrl)->assertInertia(fn (Assert $page) => $page->where('title', 'Original physics course'));
    $this->get(route('courses'))->assertInertia(fn (Assert $page) => $page->where('courses.data.0.title', 'Original physics course'));
    $this->getJson(route('search', ['term' => 'astronomy']))->assertExactJson([]);
    $this->getJson(route('search', ['term' => 'physics']))->assertJsonPath('0.title', 'Original physics course');

    $this->actingAs($this->author)->post(route('studio.courses.publish', $this->course), ['revision' => 1])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($this->course->fresh()->category_id)->toBe($newCategory->id)
        ->and($this->course->draft()->exists())->toBeFalse();
    $this->actingAs($this->learner)->get($courseUrl)->assertInertia(fn (Assert $page) => $page
        ->where('title', 'Revised astronomy course')->where('slug', 'original-physics')->where('description', '<p>New description</p>'));
    $this->getJson(route('search', ['term' => 'astronomy']))->assertJsonPath('0.title', 'Revised astronomy course');
    $this->get(route('courses'))->assertInertia(fn (Assert $page) => $page->where('courses.data.0.title', 'Revised astronomy course'));
});

it('protects all draft writes with the course revision and keeps a previously published URL fixed', function () {
    $anotherEditor = User::factory()->admin()->create();
    $update = route('studio.courses.update', $this->course);
    $fields = ['revision' => 0, 'title' => 'First title', 'category' => $this->course->category_id, 'description_type' => 'html'];
    $this->patchJson($update, [...$fields, 'slug' => 'different-url'])->assertUnprocessable()->assertJsonValidationErrors('slug');
    expect($this->course->draft()->exists())->toBeFalse();

    $this->patch($update, $fields)->assertSessionHasNoErrors();
    $this->actingAs($anotherEditor)
        ->patchJson($update, [...$fields, 'title' => 'Stale form title'])->assertConflict();
    $this->postJson(route('studio.courses.publish', $this->course), ['revision' => 0])->assertConflict();
    $this->deleteJson(route('studio.courses.draft.destroy', $this->course), ['revision' => 0])->assertConflict();
    $this->actingAs($this->learner)->get(route('studio.courses.show', $this->course))->assertForbidden();
    expect($this->course->fresh()->draft->title)->toBe('First title');
});

it('moves and adds lessons without changing existing lesson identities or losing completions', function () {
    $secondChapter = Chapter::factory()->for($this->course)->create(['position' => 2]);
    $secondLesson = Lesson::factory()->for($secondChapter)->for($this->course)->create(['position_within_course' => 2]);
    $graduate = User::factory()->create();
    $graduate->enrolledCourses()->create(['course_id' => $this->course->id, 'progress' => 0, 'started_at' => now()]);
    $completion = $this->lesson->markCompletedFor($this->learner);
    $this->lesson->markCompletedFor($graduate);
    $secondLesson->markCompletedFor($graduate);
    $completedAt = $graduate->findEnrollmentFor($this->course)->completed_at;
    $lessonUrl = route('lessons.show', [$this->course->slug, $this->lesson->slug_id]);

    $this->patch(route('studio.course.lessons.update', [$this->course, $this->chapter, $this->lesson]), [
        'revision' => 0, 'title' => 'Revised lesson title', 'description_type' => 'html', 'resources' => [],
    ])->assertSessionHasNoErrors();
    $this->post(route('studio.course.lessons.move', $this->course), [
        'revision' => 1, 'source_chapter' => $this->chapter->uuid, 'destination_chapter' => $secondChapter->uuid,
        'lesson' => $this->lesson->uuid, 'destination_index' => 1,
    ])->assertSessionHasNoErrors();
    $this->post(route('studio.course.lessons.store', [$this->course, $this->chapter]), ['revision' => 2])->assertSessionHasNoErrors();
    $draftLesson = $this->course->fresh()->draft->lessons()->whereNull('title')->firstOrFail();
    $this->get(route('studio.course.lessons.show', [$this->course, $draftLesson->uuid]))
        ->assertInertia(fn (Assert $page) => $page->where('lesson.id', $draftLesson->uuid));

    $this->actingAs($this->learner)->get($lessonUrl)->assertInertia(fn (Assert $page) => $page
        ->where('lessonTitle', 'Original lesson')->where('totalLessons', 2));
    $this->postJson(route('completed-lessons.store', $draftLesson->uuid))->assertNotFound();

    $this->actingAs($this->author)->post(route('studio.courses.publish', $this->course), ['revision' => 3])->assertSessionHasNoErrors();

    expect($this->lesson->fresh()->chapter_id)->toBe($secondChapter->id)
        ->and($this->learner->findEnrollmentFor($this->course)->progress)->toBe(34)
        ->and($completion->fresh())->not->toBeNull();
    $this->actingAs($graduate)->get($lessonUrl)->assertInertia(fn (Assert $page) => $page
        ->where('lessonTitle', 'Revised lesson title')->where('totalLessons', 3)
        ->where('progress', 100)->where('remainingLessons', 1)->where('isCompleted', true));
    $this->post(route('completed-lessons.store', $draftLesson->uuid))->assertRedirect();
    expect($graduate->findEnrollmentFor($this->course)->completed_at->equalTo($completedAt))->toBeTrue();
});

it('stages attachment removal and replacement, and deletes only files released by publication', function () {
    config(['filesystems.content_disk' => 'public']);
    Storage::fake('public');
    Storage::disk('public')->put('course-resources/original.pdf', 'published PDF');
    $original = $this->course->resources()->create([
        'file_path' => 'course-resources/original.pdf', 'client_file_name' => 'original.pdf', 'size' => 13, 'mime_type' => 'application/pdf',
    ]);
    $this->lesson->resources()->attach($original);
    $temporary = $this->postJson(route('files.store'), [
        'scope' => 'CourseResource', 'file' => UploadedFile::fake()->createWithContent('updated.pdf', 'updated PDF'),
    ])->assertSuccessful()->json('id');
    $this->patch(route('studio.course.lessons.update', [$this->course, $this->chapter, $this->lesson]), [
        'revision' => 0, 'title' => $this->lesson->title, 'description_type' => 'html',
        'resources' => [['id' => $temporary, 'type' => 'temporary']],
    ])->assertSessionHasNoErrors();

    $replacement = $this->course->fresh()->draft->resources()->firstOrFail();
    $download = fn (Resource $resource) => route('resources.show', [$this->course->slug, $resource->uuid]);
    $this->actingAs($this->learner)->get($download($original))->assertDownload('original.pdf');
    $this->get($download($replacement))->assertNotFound();
    Storage::disk('public')->assertExists($original->file_path);

    $this->actingAs($this->author)->post(route('studio.courses.publish', $this->course), ['revision' => 1])->assertSessionHasNoErrors();
    $this->actingAs($this->learner)->get($download($original))->assertNotFound();
    $this->get($download($replacement))->assertDownload('updated.pdf');
    Storage::disk('public')->assertMissing($original->file_path)->assertExists($replacement->file_path);
});

it('keeps the original video playable when the draft upload or its processing blocks publication', function () {
    config(['filesystems.content_disk' => 'public']);
    Storage::fake('public');
    Queue::fake();
    $original = Video::factory()->ready()->create();
    $this->lesson->video()->associate($original)->save();

    $upload = $this->postJson(route('studio.video-uploads.store', $this->course), [
        'revision' => 0, 'uuid' => (string) Str::uuid(), 'lesson_id' => $this->lesson->uuid, 'filename' => 'clip.mp4', 'size' => 12,
    ])->assertCreated()->json('id');
    $this->postJson(route('studio.courses.publish', $this->course), ['revision' => 0])
        ->assertUnprocessable()->assertJsonValidationErrors('publication');
    $this->postJson(route('files.store'), [
        'scope' => 'CourseVideo', 'video_upload_id' => $upload,
        'file' => UploadedFile::fake()->createWithContent('clip.mp4', 'source video'),
    ])->assertSuccessful();
    $replacement = $this->postJson(route('studio.video-uploads.complete', $upload))
        ->assertSuccessful()->assertJsonPath('editorRevision.after', 1)->json('videoId');
    Video::findOrFail($replacement)->update(['processing_status' => VideoProcessingStatus::Failed]);
    $this->postJson(route('studio.courses.publish', $this->course), ['revision' => 1])
        ->assertUnprocessable()->assertJsonValidationErrors('publication');

    $this->actingAs($this->learner)
        ->get(route('lessons.show', [$this->course->slug, $this->lesson->slug_id]))
        ->assertInertia(fn (Assert $page) => $page->where('video.id', $original->id));
    expect($this->course->fresh()->status)->toBe(CourseStatus::Published);

    Video::findOrFail($replacement)->update([
        'processing_status' => VideoProcessingStatus::Ready, 'duration_seconds' => 45, 'poster_image_file_path' => 'poster.jpg',
    ]);
    $this->actingAs($this->author)->post(route('studio.courses.publish', $this->course), ['revision' => 1])->assertSessionHasNoErrors();
    expect($this->lesson->fresh()->video_id)->toBe($replacement)
        ->and($this->course->fresh()->duration_seconds)->toBe(45);
});

it('keeps the published cover file when a draft cover is discarded', function () {
    config(['filesystems.content_disk' => 'public']);
    Storage::fake('public');
    $original = 'course-covers/original.jpg';
    Storage::disk('public')->put($original, 'published cover');
    Storage::disk('public')->put('tmp/replacement.jpg', 'draft cover');
    $this->course->update(['cover_image_file_path' => $original]);
    $upload = TemporaryUpload::create([
        'user_id' => $this->author->id, 'scope' => 'CourseCoverImage', 'disk' => 'public',
        'path' => 'tmp/replacement.jpg', 'size' => 11, 'mime_type' => 'image/jpeg', 'client_file_name' => 'replacement.jpg',
    ]);

    $this->patch(route('studio.courses.update', $this->course), [
        'revision' => 0, 'title' => $this->course->title, 'category' => $this->course->category_id,
        'description_type' => 'html', 'cover_image' => $upload->uuid,
    ])->assertSessionHasNoErrors();

    $replacement = $this->course->fresh()->draft->cover_image_file_path;
    Storage::disk('public')->assertExists([$original, $replacement]);
    expect($this->course->fresh()->cover_image_file_path)->toBe($original);

    $this->delete(route('studio.courses.draft.destroy', $this->course), ['revision' => 1])->assertSessionHasNoErrors();
    Storage::disk('public')->assertMissing($replacement)->assertExists($original);
});

it('cancels draft uploads and restores the published outline when the author discards changes', function () {
    config(['filesystems.content_disk' => 'public']);
    Storage::fake('public');
    $this->patch(route('studio.course.chapters.update', [$this->course, $this->chapter]), [
        'revision' => 0, 'title' => 'New chapter title',
    ])->assertSessionHasNoErrors();
    $upload = $this->postJson(route('studio.video-uploads.store', $this->course), [
        'revision' => 1, 'uuid' => (string) Str::uuid(), 'lesson_id' => $this->lesson->uuid, 'filename' => 'clip.mp4', 'size' => 12,
    ])->assertCreated()->json('id');
    $this->postJson(route('files.store'), [
        'scope' => 'CourseVideo', 'video_upload_id' => $upload,
        'file' => UploadedFile::fake()->createWithContent('clip.mp4', 'source video'),
    ])->assertSuccessful();

    $this->delete(route('studio.courses.draft.destroy', $this->course), ['revision' => 1])
        ->assertRedirect(route('studio.courses.show', $this->course));
    $this->getJson(route('studio.video-uploads.show', $upload))->assertJsonPath('status', 'cancelled');
    $this->postJson(route('studio.video-uploads.complete', $upload))->assertConflict();
    $this->get(route('studio.course.chapters.show', [$this->course, $this->chapter]))
        ->assertInertia(fn (Assert $page) => $page->where('chapter.title', 'Original chapter')->where('revision', 2));
});

it('keeps the draft through unpublishing and applies access changes independently', function () {
    $this->patch(route('studio.courses.update', $this->course), [
        'revision' => 0, 'title' => 'Next publication', 'category' => $this->course->category_id, 'description_type' => 'html',
    ])->assertSessionHasNoErrors();
    $this->patch(route('studio.courses.access.update', $this->course), ['access_mode' => CourseAccessMode::Private->value])
        ->assertSessionHasNoErrors();
    $this->actingAs($this->learner)->get(route('courses.show', $this->course->slug))->assertNotFound();

    $this->actingAs($this->author)->post(route('studio.courses.unpublish', $this->course), ['revision' => 1])->assertSessionHasNoErrors();
    $this->get(route('studio.courses.show', $this->course))->assertInertia(fn (Assert $page) => $page
        ->where('title', 'Next publication')->where('status', 'unpublished')->where('revision', 2));
    $this->post(route('studio.courses.publish', $this->course), ['revision' => 2])->assertSessionHasNoErrors();
    expect($this->course->fresh()->status)->toBe(CourseStatus::Published)
        ->and($this->course->fresh()->title)->toBe('Next publication')
        ->and($this->course->fresh()->access_mode)->toBe(CourseAccessMode::Private);
});

it('recalculates stale positions and duration when republishing a legacy course without a draft', function () {
    $this->course->update(['status' => CourseStatus::Unpublished]);
    $video = Video::factory()->ready()->create(['duration_seconds' => 45]);
    $this->lesson->update(['position_within_course' => 0, 'video_id' => $video->id]);

    $this->post(route('studio.courses.publish', $this->course), ['revision' => 0])->assertSessionHasNoErrors();

    expect($this->course->fresh()->duration_seconds)->toBe(45)
        ->and($this->course->fresh()->status)->toBe(CourseStatus::Published)
        ->and($this->lesson->fresh()->position_within_course)->toBe(1);
});

it('keeps the live outline when deleting the only lesson and cleans up removed completions after a valid publication', function () {
    $completion = $this->lesson->markCompletedFor($this->learner);
    $this->delete(route('studio.course.lessons.destroy', [$this->course, $this->chapter, $this->lesson]), ['revision' => 0])
        ->assertSessionHasNoErrors();
    $this->postJson(route('studio.courses.publish', $this->course), ['revision' => 1])
        ->assertUnprocessable()->assertJsonValidationErrors('publication');
    expect($this->lesson->fresh())->not->toBeNull()->and($completion->fresh())->not->toBeNull();

    $this->post(route('studio.course.lessons.store', [$this->course, $this->chapter]), ['revision' => 1])->assertSessionHasNoErrors();
    $this->post(route('studio.courses.publish', $this->course), ['revision' => 2])->assertSessionHasNoErrors();
    expect($this->lesson->fresh())->toBeNull()->and($completion->fresh())->toBeNull()
        ->and($this->learner->findEnrollmentFor($this->course)->isCompleted())->toBeTrue();
});
