<?php

use App\Enums\CourseStatus;
use App\Enums\VideoProcessingStatus;
use App\Jobs\PublishCourse;
use App\Models\Author;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Video;
use App\Models\VideoUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->course = Course::factory()->for(Author::factory())->for(Category::factory())->create(['status' => CourseStatus::Draft]);
    $chapter = Chapter::factory()->for($this->course)->create();
    $this->lesson = Lesson::factory()->for($chapter)->for($this->course)->create();
});

it('publishes a text-only course and blocks active uploads', function () {
    expect($this->course->canBePublished())->toBeTrue();
    $upload = VideoUpload::factory()->for($this->course)->create();
    expect($this->course->canBePublished())->toBeFalse();
    expect(fn () => $this->course->publish())->toThrow(LogicException::class);
    $upload->update(['expires_at' => now()->subMinute()]);
    Bus::fake();
    $this->course->publish();
    expect($this->course->fresh()->status)->toBe(CourseStatus::Publishing);
});

it('requires both lesson videos and the trailer to be ready', function () {
    $video = Video::factory()->create();
    $this->lesson->video()->associate($video)->save();
    expect($this->course->canBePublished())->toBeFalse();
    $video->update(['processing_status' => VideoProcessingStatus::Ready, 'duration_seconds' => 30, 'poster_image_file_path' => 'poster.jpg']);
    expect($this->course->canBePublished())->toBeTrue();
    $trailer = Video::factory()->create();
    $this->course->trailer()->associate($trailer)->save();
    expect($this->course->canBePublished())->toBeFalse();
});

it('rechecks readiness in the final publication job', function () {
    $this->course->update(['status' => CourseStatus::Publishing]);
    $video = Video::factory()->create();
    $this->lesson->video()->associate($video)->save();
    expect(fn () => (new PublishCourse($this->course))->handle())->toThrow(LogicException::class);
    expect($this->course->fresh()->status)->toBe(CourseStatus::Publishing);
});
