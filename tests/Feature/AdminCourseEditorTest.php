<?php

use App\Enums\CourseAccessMode;
use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->admin = User::factory()->admin()->create();
    $this->author = User::factory()->author()->create();
    $this->course = Course::factory()->for($this->author->author)->for(Category::factory())->create([
        'title' => 'Published course',
        'access_mode' => CourseAccessMode::Public,
    ]);
    $this->chapter = Chapter::factory()->for($this->course)->create();
    $this->lesson = Lesson::factory()->for($this->course)->for($this->chapter)->create(['title' => 'Published lesson']);
    $this->actingAs($this->admin);
});

it('opens the shared editor from the admin course list and keeps the admin navigation', function () {
    $url = route('admin.course-editor.courses.show', $this->course);

    expect($this->admin->author)->toBeNull();

    $this->get(route('admin.courses'))->assertInertia(fn (Assert $page) => $page
        ->component('Admin/CourseListPage')
        ->where('courses.rows.0.cells.0.link.url', $url)
        ->where('courses.rows.0.actions.0.label', 'Manage')
        ->where('courses.rows.0.actions.0.url', $url)
        ->where('courses.rows.0.actions.0.canRun', true));

    $this->get($url)->assertInertia(fn (Assert $page) => $page
        ->component('Studio/CourseDetail')
        ->where('courseEditorPrefix', 'admin.course-editor')
        ->where('breadcrumbs.0.action.url', route('admin'))
        ->where('breadcrumbs.1.action.url', route('admin.courses'))
        ->where('previewOptions.0.url', route('admin.course-editor.courses.preview.show', [$this->course, 'published']))
        ->where('isEditable', true));

    $this->get(route('admin.course-editor.courses.content', $this->course))->assertInertia(fn (Assert $page) => $page
        ->component('Studio/CourseContent')
        ->where('courseEditorPrefix', 'admin.course-editor'));
    $this->get(route('admin.course-editor.courses.access', $this->course))->assertInertia(fn (Assert $page) => $page
        ->component('Studio/CourseAccess')
        ->where('courseEditorPrefix', 'admin.course-editor'));
});

it('lets an admin publish the shared draft while preserving the published course and lesson identities', function () {
    $editUrl = route('admin.course-editor.courses.show', $this->course);
    $learner = User::factory()->create();
    $learner->enrolledCourses()->create(['course_id' => $this->course->id, 'progress' => 0, 'started_at' => now()]);
    $completion = $this->lesson->markCompletedFor($learner);
    $lessonUrl = route('lessons.show', [$this->course->slug, $this->lesson->slug_id]);

    $this->from($editUrl)->patch(route('admin.course-editor.courses.update', $this->course), [
        'revision' => 0, 'title' => 'Revised course', 'category' => $this->course->category_id, 'description_type' => 'html',
    ])->assertRedirect($editUrl)->assertSessionHasNoErrors();

    $lessonFields = ['title' => 'Revised lesson', 'description_type' => 'html', 'resources' => []];
    $updateLesson = route('admin.course-editor.course.lessons.update', [$this->course, $this->chapter, $this->lesson]);
    $this->patchJson($updateLesson, [...$lessonFields, 'revision' => 0])->assertConflict();
    $this->patch($updateLesson, [...$lessonFields, 'revision' => 1])->assertSessionHasNoErrors();

    $this->get(route('admin.courses'))->assertInertia(fn (Assert $page) => $page
        ->where('courses.rows.0.cells.2.props.text', 'Published')
        ->where('courses.rows.0.cells.2.props.variant', 'positive'));

    $this->actingAs($this->author)->get(route('studio.courses'))->assertInertia(fn (Assert $page) => $page
        ->where('courses.rows.0.cells.1.props.text', 'Published · Unpublished changes')
        ->where('courses.rows.0.cells.1.props.variant', 'warning'));

    $this->actingAs($this->author)->get(route('studio.course.lessons.show', [$this->course, $this->lesson]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('courseEditorPrefix', 'studio')
            ->where('breadcrumbs.1.action.url', route('studio.courses'))
            ->where('title', 'Revised course')
            ->where('lesson.title', 'Revised lesson')
            ->where('revision', 2));

    $this->actingAs($learner)->get($lessonUrl)->assertInertia(fn (Assert $page) => $page
        ->where('courseTitle', 'Published course')
        ->where('lessonTitle', 'Published lesson'));

    $this->actingAs($this->admin)->from($editUrl)->post(route('admin.course-editor.courses.publish', $this->course), [
        'revision' => 2,
    ])->assertRedirect($editUrl)->assertSessionHasNoErrors();

    expect($this->course->fresh()->author_id)->toBe($this->author->author->id)
        ->and($this->course->draft()->exists())->toBeFalse()
        ->and($completion->fresh())->not->toBeNull();

    $this->actingAs($learner)->get($lessonUrl)->assertInertia(fn (Assert $page) => $page
        ->where('courseTitle', 'Revised course')
        ->where('lessonTitle', 'Revised lesson')
        ->where('isCompleted', true));
});

it('keeps both preview versions, lesson links, and downloads within the admin route group', function () {
    config(['filesystems.content_disk' => 'public']);
    Storage::fake('public')->put('course-resources/preview.pdf', 'preview PDF');
    $resource = $this->course->resources()->create([
        'file_path' => 'course-resources/preview.pdf', 'client_file_name' => 'preview.pdf', 'size' => 11, 'mime_type' => 'application/pdf',
    ]);
    $this->lesson->resources()->attach($resource);

    $this->post(route('admin.course-editor.course.chapters.store', $this->course), [
        'revision' => 0, 'lesson' => 1,
    ])->assertSessionHasNoErrors()->assertRedirectToRoute('admin.course-editor.course.lessons.show', [
        $this->course, $this->course->fresh()->draft->lessons()->whereNull('title')->firstOrFail()->uuid,
    ]);

    $editUrl = route('admin.course-editor.courses.show', $this->course);
    $draftUrl = route('admin.course-editor.courses.preview.show', [$this->course, 'draft']);
    $publishedUrl = route('admin.course-editor.courses.preview.show', [$this->course, 'published']);
    $lessonUrl = route('admin.course-editor.courses.preview.lesson', [$this->course, 'draft', $this->lesson->uuid]);
    $downloadUrl = route('admin.course-editor.courses.preview.resource', [$this->course, 'draft', $this->lesson->uuid, $resource->uuid]);

    $this->getJson(route('admin.course-editor.videos', $this->course))
        ->assertJsonPath('previewOptions.0.url', $draftUrl)
        ->assertJsonPath('previewOptions.1.url', $publishedUrl);

    $response = $this->get($draftUrl)->assertInertia(fn (Assert $page) => $page
        ->where('preview.studioUrl', $editUrl)
        ->where('preview.returnLabel', 'Back to editor')
        ->where('chapters.0.lessons.0.url', $lessonUrl)
        ->where('chapters.1.lessons.0.title', 'Untitled'));
    expect($response->headers->hasCacheControlDirective('no-store'))->toBeTrue();
    $this->get($publishedUrl)->assertInertia(fn (Assert $page) => $page->has('chapters', 1));
    $this->get($lessonUrl)->assertInertia(fn (Assert $page) => $page
        ->where('preview.courseUrl', $draftUrl)
        ->where('preview.studioUrl', $editUrl)
        ->where('resources.0.url', $downloadUrl));
    $this->get($downloadUrl)->assertDownload('preview.pdf');

    $this->delete(route('admin.course-editor.courses.draft.destroy', $this->course), ['revision' => 1])
        ->assertRedirect($editUrl);
    $this->get($draftUrl)->assertNotFound();
});

it('uses the authenticated administrator for access grants and video uploads', function () {
    $learner = User::factory()->create();
    $accessUrl = route('admin.course-editor.courses.access', $this->course);
    $this->from($accessUrl)->patch(route('admin.course-editor.courses.access.update', $this->course), [
        'access_mode' => CourseAccessMode::Private->value,
    ])->assertRedirect($accessUrl)->assertSessionHasNoErrors();
    $this->post(route('admin.course-editor.courses.access.users.store', $this->course), [
        'users' => [$learner->id],
    ])->assertRedirect($accessUrl)->assertSessionHasNoErrors();
    $this->assertDatabaseHas('course_user_access', [
        'course_id' => $this->course->id,
        'user_id' => $learner->id,
        'granted_by_id' => $this->admin->id,
    ]);

    $video = Video::factory()->ready()->create();
    $this->lesson->video()->associate($video)->save();
    $uploadId = (string) Str::uuid();
    $fields = ['lesson_id' => $this->lesson->uuid, 'filename' => 'clip.mp4', 'size' => 12];
    $this->postJson(route('admin.course-editor.video-uploads.store', $this->course), [
        ...$fields, 'revision' => 0, 'uuid' => $uploadId,
    ])->assertCreated()->assertJsonPath('id', $uploadId);
    expect(VideoUpload::query()->where('uuid', $uploadId)->firstOrFail()->user_id)->toBe($this->admin->id);
    $this->deleteJson(route('admin.course-editor.video-uploads.destroy', [$this->course, $uploadId]), $fields)
        ->assertSuccessful()->assertJsonPath('status', 'cancelled');

    $this->deleteJson(route('admin.course-editor.videos.destroy', [$this->course, $video]), [
        'revision' => 0, 'lesson_id' => $this->lesson->uuid,
    ])->assertSuccessful()->assertJsonPath('editorRevision.after', 1);
    expect($this->course->fresh()->draft->lessons()->where('uuid', $this->lesson->uuid)->firstOrFail()->video_id)->toBeNull()
        ->and($this->lesson->fresh()->video_id)->toBe($video->id);
});

it('requires admin access even for the owner and preserves their studio access', function () {
    $editUrl = route('admin.course-editor.courses.show', $this->course);
    $previewUrl = route('admin.course-editor.courses.preview.show', [$this->course, 'published']);
    $updateUrl = route('admin.course-editor.courses.update', $this->course);

    foreach ([$this->author, User::factory()->create()] as $person) {
        $this->actingAs($person)->get($editUrl)->assertForbidden();
        $this->get($previewUrl)->assertForbidden();
        $this->patchJson($updateUrl, ['revision' => 0])->assertForbidden();
    }

    $this->actingAs($this->author)->get(route('studio.courses.show', $this->course))->assertOk();
    expect($this->course->draft()->exists())->toBeFalse();
});

it('returns to the correct course list after a permitted deletion', function () {
    $editUrl = route('admin.course-editor.courses.show', $this->course);
    $deleteUrl = route('admin.course-editor.courses.destroy', $this->course);
    $this->deleteJson($deleteUrl, ['revision' => 0])->assertConflict();
    $this->from($editUrl)->post(route('admin.course-editor.courses.unpublish', $this->course), ['revision' => 0])
        ->assertRedirect($editUrl);
    $this->delete($deleteUrl, ['revision' => 1])->assertRedirect(route('admin.courses'));
    $this->assertSoftDeleted($this->course);

    $authorDraft = Course::factory()->for($this->author->author)->create(['status' => CourseStatus::Draft]);
    $this->actingAs($this->author)->delete(route('studio.courses.destroy', $authorDraft))
        ->assertRedirect(route('studio.courses'));
});
