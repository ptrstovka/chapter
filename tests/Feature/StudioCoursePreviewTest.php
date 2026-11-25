<?php

use App\Enums\CourseAccessMode;
use App\Enums\CourseStatus;
use App\Enums\VideoProcessingStatus;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->author = User::factory()->author()->create();
    $this->course = Course::factory()->for($this->author->author)->for(Category::factory())->create([
        'title' => 'Published course',
        'access_mode' => CourseAccessMode::Private,
    ]);
    $this->chapter = Chapter::factory()->for($this->course)->create(['title' => 'Published chapter']);
    $this->lesson = Lesson::factory()->for($this->course)->for($this->chapter)->create(['title' => 'Published lesson']);
    $this->actingAs($this->author);
});

it('previews an empty course before its first publication without a slug or an enrollment', function () {
    $emptyCourse = Course::factory()->for($this->author->author)->create([
        'status' => CourseStatus::Draft, 'slug' => null, 'title' => '  ', 'category_id' => null,
        'description' => '<p>&nbsp;<br></p>',
    ]);
    $previewUrl = route('studio.courses.preview.show', [$emptyCourse, 'draft']);

    $this->get(route('studio.courses.show', $emptyCourse))
        ->assertInertia(fn (Assert $page) => $page
            ->has('previewOptions', 1)
            ->where('previewOptions.0.url', $previewUrl));
    $response = $this->get($previewUrl)->assertInertia(fn (Assert $page) => $page
        ->component('Courses/CourseDetail')
        ->where('title', 'Untitled course')
        ->where('slug', null)
        ->where('description', null)
        ->where('trailer', null)
        ->where('enrollment', null)
        ->where('access', null)
        ->where('preview.version', 'draft')
        ->where('preview.courseUrl', $previewUrl)
        ->where('preview.studioUrl', route('studio.courses.show', $emptyCourse))
        ->has('chapters', 0));
    expect($response->headers->hasCacheControlDirective('no-store'))->toBeTrue();

    $this->get(route('studio.courses.preview.show', [$emptyCourse, 'published']))->assertNotFound();
    $this->get(route('studio.courses.preview.show', [$emptyCourse, 'unknown']))->assertNotFound();
    expect($emptyCourse->draft()->exists())->toBeFalse();
});

it('keeps the outline and lesson links on the selected saved version before and after unpublishing', function () {
    $publishedUrl = route('studio.courses.preview.show', [$this->course, 'published']);
    $draftUrl = route('studio.courses.preview.show', [$this->course, 'draft']);
    $this->get($draftUrl)->assertNotFound();
    $this->get(route('studio.courses.show', $this->course))
        ->assertInertia(fn (Assert $page) => $page->has('previewOptions', 1)->where('previewOptions.0.url', $publishedUrl));

    $this->patch(route('studio.courses.update', $this->course), [
        'revision' => 0, 'title' => 'Draft course', 'category' => $this->course->category_id, 'description_type' => 'html',
    ])->assertSessionHasNoErrors();
    $this->post(route('studio.course.lessons.store', [$this->course, $this->chapter]), ['revision' => 1])
        ->assertSessionHasNoErrors();
    $draftLesson = $this->course->fresh()->draft->lessons()->whereNull('title')->firstOrFail();
    $draftLessonUrl = route('studio.courses.preview.lesson', [$this->course, 'draft', $draftLesson->uuid]);
    $publishedLessonUrl = route('studio.courses.preview.lesson', [$this->course, 'published', $this->lesson->uuid]);

    $this->get(route('studio.courses.show', $this->course))->assertInertia(fn (Assert $page) => $page
        ->where('previewOptions', [
            ['label' => 'Draft', 'url' => $draftUrl],
            ['label' => 'Published version', 'url' => $publishedUrl],
        ]));
    $this->get($publishedUrl)->assertInertia(fn (Assert $page) => $page
        ->where('title', 'Published course')
        ->where('chapters.0.lessons.0.url', $publishedLessonUrl)
        ->has('chapters.0.lessons', 1));
    $this->get($draftUrl)->assertInertia(fn (Assert $page) => $page
        ->where('title', 'Draft course')
        ->where('chapters.0.lessons.1.title', 'Untitled')
        ->where('chapters.0.lessons.1.url', $draftLessonUrl)
        ->has('chapters.0.lessons', 2));
    $this->get($draftLessonUrl)->assertInertia(fn (Assert $page) => $page
        ->component('Courses/CourseLesson')
        ->where('preview.courseUrl', $draftUrl)
        ->where('lessonTitle', 'Untitled')
        ->where('courseSlug', null)
        ->where('video', null)
        ->where('description', null)
        ->where('chapters.0.lessons.1.isCurrent', true)
        ->where('chapters.0.lessons.0.url', route('studio.courses.preview.lesson', [$this->course, 'draft', $this->lesson->uuid]))
        ->missing('progress'));
    $this->get(route('studio.courses.preview.lesson', [$this->course, 'published', $draftLesson->uuid]))->assertNotFound();

    $this->post(route('studio.courses.unpublish', $this->course), ['revision' => 2])->assertSessionHasNoErrors();
    $this->get($publishedUrl)->assertInertia(fn (Assert $page) => $page
        ->where('preview.versionLabel', 'Last published version')
        ->where('title', 'Published course'));
    $this->get($draftUrl)->assertInertia(fn (Assert $page) => $page->where('title', 'Draft course'));

    $this->delete(route('studio.courses.draft.destroy', $this->course), ['revision' => 3])->assertSessionHasNoErrors();
    $this->get($draftLessonUrl)->assertNotFound();
    $this->get($publishedLessonUrl)->assertSuccessful();
    $this->assertDatabaseCount('course_enrollments', 0);
    $this->assertDatabaseCount('course_access_requests', 0);
    $this->assertDatabaseCount('completed_lessons', 0);
});

it('limits preview pages and downloads to the course author and administrators', function () {
    config(['filesystems.content_disk' => 'public']);
    Storage::fake('public')->put('course-resources/preview.pdf', 'preview PDF');
    $resource = $this->course->resources()->create([
        'file_path' => 'course-resources/preview.pdf', 'client_file_name' => 'preview.pdf', 'size' => 11, 'mime_type' => 'application/pdf',
    ]);
    $this->lesson->resources()->attach($resource);
    $arguments = [$this->course, 'published', $this->lesson->uuid];
    $previewUrl = route('studio.courses.preview.show', [$this->course, 'published']);
    $lessonUrl = route('studio.courses.preview.lesson', $arguments);
    $resourceUrl = route('studio.courses.preview.resource', [...$arguments, $resource->uuid]);

    $this->get($lessonUrl)->assertSuccessful();
    $this->get($resourceUrl)->assertDownload('preview.pdf');

    $this->actingAs(User::factory()->admin()->create());
    $this->get($previewUrl)->assertSuccessful();
    $this->get($resourceUrl)->assertDownload('preview.pdf');

    foreach ([User::factory()->author()->create(), User::factory()->create()] as $otherUser) {
        $this->actingAs($otherUser);
        $this->get($previewUrl)->assertForbidden();
        $this->get($lessonUrl)->assertForbidden();
        $this->get($resourceUrl)->assertForbidden();
    }
});

it('scopes filenames and downloads to the version and lesson even when resource UUIDs match', function () {
    config(['filesystems.content_disk' => 'public']);
    Storage::fake('public')->put('course-resources/original.pdf', 'preview PDF');
    $otherLesson = Lesson::factory()->for($this->course)->for($this->chapter)->create(['position' => 2]);
    $original = $this->course->resources()->create([
        'file_path' => 'course-resources/original.pdf', 'client_file_name' => 'original.pdf', 'size' => 11, 'mime_type' => 'application/pdf',
    ]);
    $this->lesson->resources()->attach($original);

    $this->patch(route('studio.course.lessons.update', [$this->course, $this->chapter, $this->lesson]), [
        'revision' => 0, 'title' => 'Draft lesson', 'description_type' => 'html',
        'resources' => [['type' => 'existing', 'id' => $original->uuid, 'name' => 'draft.pdf']],
    ])->assertSessionHasNoErrors();

    $downloadUrl = fn (string $version, Lesson $lesson) => route('studio.courses.preview.resource', [$this->course, $version, $lesson->uuid, $original->uuid]);
    $this->get($downloadUrl('published', $this->lesson))->assertDownload('original.pdf');
    $this->get($downloadUrl('draft', $this->lesson))->assertDownload('draft.pdf');
    $this->get($downloadUrl('draft', $otherLesson))->assertNotFound();

    $draft = $this->course->fresh()->draft;
    $resource = $draft->resources()->where('uuid', $original->uuid)->firstOrFail();
    $resource->update(['file_path' => 'missing.pdf']);
    $this->get($downloadUrl('draft', $this->lesson))->assertNotFound();
    $this->get($downloadUrl('published', $this->lesson))->assertDownload('original.pdf');
});

it('supplies fallback labels in the editor, preview and learner view and exposes video processing state safely', function () {
    $video = Video::factory()->create(['processing_status' => VideoProcessingStatus::Processing]);
    $this->course->update(['access_mode' => CourseAccessMode::Public, 'trailer_id' => $video->id]);
    $this->chapter->update(['title' => '  ']);
    $this->lesson->update(['title' => null, 'slug' => null, 'video_id' => $video->id, 'description' => '<p><br></p>']);

    $this->get(route('studio.course.lessons.show', [$this->course, $this->lesson]))
        ->assertInertia(fn (Assert $page) => $page->where('lesson.title', null)
            ->where('lesson.displayTitle', 'Untitled')
            ->where('chapters.0.lessons.0.title', 'Untitled'));
    $this->get(route('studio.courses.preview.show', [$this->course, 'published']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('chapters.0.title', 'Chapter 1')
            ->where('chapters.0.lessons.0.title', 'Untitled')
            ->where('trailer.url', null)
            ->where('trailer.status', 'processing'));
    $this->get(route('studio.courses.preview.lesson', [$this->course, 'published', $this->lesson->uuid]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('lessonTitle', 'Untitled')
            ->where('description', null)
            ->where('video.url', null)
            ->where('video.status', 'processing'));

    $learner = User::factory()->create();
    $learner->enrolledCourses()->create(['course_id' => $this->course->id, 'progress' => 0]);
    $this->actingAs($learner)->get(route('lessons.show', [$this->course->slug, $this->lesson->slug_id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('lessonTitle', 'Untitled')
            ->where('chapters.0.lessons.0.title', 'Untitled')
            ->where('progress', 0)
            ->missing('preview'));
});
