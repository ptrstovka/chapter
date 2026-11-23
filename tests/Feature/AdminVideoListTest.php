<?php

use App\Enums\VideoFormat;
use App\Enums\VideoProcessingStatus;
use App\Enums\VideoUploadStatus;
use App\Models\Author;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function adminVideoCell(TestResponse $response, Video $video, string $heading): mixed
{
    $column = collect($response->inertiaProps('videos.headings'))->firstWhere('name', __($heading))['id'];
    $row = collect($response->inertiaProps('videos.rows'))->firstWhere('key', $video->id);
    $props = collect($row['cells'])->firstWhere('column', $column)['props'];

    return $props['value'] ?? $props['text'];
}

it('requires an authenticated administrator to view videos', function () {
    $this->get(route('admin.videos'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())->get(route('admin.videos'))->assertForbidden();
});

it('lists all videos and their usages without reading storage or exposing actions and internal data', function () {
    $admin = User::factory()->admin()->create();
    $course = Course::factory()->for(Author::factory())->for(Category::factory())->create(['title' => 'Video course']);
    $chapter = Chapter::factory()->for($course)->create(['title' => 'First chapter']);
    $lessonVideo = Video::factory()->ready()->create([
        'format' => VideoFormat::Hls,
        'hls_playlist_path' => 'videos/1/hls/master.m3u8',
        'hls_size_bytes' => 4294967296,
        'processing_error' => 'Private worker detail',
    ]);
    Lesson::factory()->for($course)->for($chapter)->for($lessonVideo)->create(['title' => 'First lesson']);
    $trailer = Video::factory()->ready()->create();
    $course->update(['trailer_id' => $trailer->id]);
    $orphan = Video::factory()->create();
    $archivedCourse = Course::factory()->for(Author::factory())->for(Category::factory())->create([
        'title' => 'Archived course',
        'trailer_id' => $lessonVideo->id,
        'deleted_at' => now(),
    ]);

    Storage::shouldReceive('disk')->never();

    $response = $this->actingAs($admin)->get(route('admin.videos'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/VideoListPage')
            ->has('videos.rows', 3)
            ->where('videos.rows.0.key', $orphan->id)
        );

    expect(adminVideoCell($response, $lessonVideo, 'Course'))->toBe("Video course\n{$archivedCourse->title}")
        ->and(adminVideoCell($response, $lessonVideo, 'Video type'))->toBe(__('Lesson')."\n".__('Trailer'))
        ->and(adminVideoCell($response, $lessonVideo, 'Chapter / lesson'))->toBe("First chapter / First lesson\n—")
        ->and(adminVideoCell($response, $trailer, 'Video type'))->toBe(__('Trailer'))
        ->and(adminVideoCell($response, $orphan, 'Video type'))->toBe(__('Unassigned'))
        ->and(adminVideoCell($response, $lessonVideo, 'HLS stream'))->toBe(__('Yes'))
        ->and(adminVideoCell($response, $lessonVideo, 'HLS size'))->toBe(Number::fileSize(4294967296))
        ->and(adminVideoCell($response, $trailer, 'HLS stream'))->toBe(__('No'))
        ->and(adminVideoCell($response, $lessonVideo, 'Status'))->toBe(__('Ready'));

    foreach ($response->inertiaProps('videos.rows') as $row) {
        expect($row['actions'])->toBeEmpty()
            ->and($row['resource'])->toBeNull();
    }

    expect(json_encode($response->inertiaProps('videos')))->not->toContain('Private worker detail', $lessonVideo->file_path, $lessonVideo->hls_playlist_path);
});

it('shows only recorded original sizes and distinguishes missing files from unknown sizes', function () {
    $known = Video::factory()->ready()->create();
    $unknown = Video::factory()->ready()->create();
    $hlsOnly = Video::factory()->ready()->create([
        'file_path' => null,
        'format' => VideoFormat::Hls,
        'hls_playlist_path' => 'videos/3/hls/master.m3u8',
    ]);
    $queued = Video::factory()->create(['format' => VideoFormat::Hls]);

    VideoUpload::factory()->for($known)->create(['status' => VideoUploadStatus::Completed, 'size' => 1048576]);
    VideoUpload::factory()->for($known)->create(['status' => VideoUploadStatus::Pending, 'size' => 2048]);
    VideoUpload::factory()->for($unknown)->create(['status' => VideoUploadStatus::Cancelled, 'size' => 4096]);
    VideoUpload::factory()->for($hlsOnly)->create(['status' => VideoUploadStatus::Completed, 'size' => 8388608]);

    $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.videos'))->assertOk();

    expect(adminVideoCell($response, $known, 'Original size'))->toBe(Number::fileSize(1048576))
        ->and(adminVideoCell($response, $unknown, 'Original size'))->toBe(__('Unknown'))
        ->and(adminVideoCell($response, $hlsOnly, 'Original size'))->toBe('—')
        ->and(adminVideoCell($response, $hlsOnly, 'Original'))->toBe(__('No'))
        ->and(adminVideoCell($response, $hlsOnly, 'HLS size'))->toBe(__('Unknown'))
        ->and(adminVideoCell($response, $known, 'HLS size'))->toBe('—')
        ->and(adminVideoCell($response, $queued, 'HLS stream'))->toBe(__('No'));
});

it('searches course chapter lesson titles and video IDs while preserving filters', function (string $search, array $filters, bool $matches) {
    $video = Video::factory()->ready()->create(['hls_playlist_path' => 'videos/1/hls/master.m3u8']);
    $course = Course::factory()->for(Author::factory())->for(Category::factory())->create([
        'title' => 'Archived photography', 'deleted_at' => now(),
    ]);
    $chapter = Chapter::factory()->for($course)->create(['title' => 'Lighting basics']);
    Lesson::factory()->for($course)->for($chapter)->for($video)->create(['title' => 'Exposure triangle']);
    Video::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.videos', ['search' => $search === 'id' ? (string) $video->id : $search, ...$filters]))
        ->assertOk();

    expect(collect($response->inertiaProps('videos.rows'))->pluck('key')->all())
        ->toBe($matches ? [$video->id] : []);
})->with([
    'course including deleted' => ['photography', [], true],
    'chapter' => ['lighting', [], true],
    'lesson with HLS' => ['exposure', ['hls' => ['yes']], true],
    'ID' => ['id', [], true],
    'ID cannot bypass HLS filter' => ['id', ['hls' => ['no']], false],
    'search cannot bypass original filter' => ['exposure', ['original' => ['no']], false],
]);

it('filters videos by usage storage and processing state', function (array $filters, array $expected) {
    $lessonVideo = Video::factory()->ready()->create(['hls_playlist_path' => 'videos/1/hls/master.m3u8', 'file_path' => null]);
    $trailer = Video::factory()->create(['processing_status' => VideoProcessingStatus::Processing]);
    $orphan = Video::factory()->create();
    $course = Course::factory()->for(Author::factory())->for(Category::factory())->create(['trailer_id' => $trailer->id]);
    $chapter = Chapter::factory()->for($course)->create();
    Lesson::factory()->for($course)->for($chapter)->for($lessonVideo)->create();

    $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.videos', $filters))->assertOk();
    $videos = ['lesson' => $lessonVideo->id, 'trailer' => $trailer->id, 'orphan' => $orphan->id];

    expect(collect($response->inertiaProps('videos.rows'))->pluck('key')->all())
        ->toBe(collect($expected)->map(fn (string $key) => $videos[$key])->all());
})->with([
    'HLS without original' => [['hls' => ['yes'], 'original' => ['no']], ['lesson']],
    'originals without HLS' => [['hls' => ['no'], 'original' => ['yes']], ['orphan', 'trailer']],
    'all HLS options' => [['hls' => ['yes', 'no']], ['orphan', 'trailer', 'lesson']],
    'lesson' => [['type' => ['lesson']], ['lesson']],
    'trailer' => [['type' => ['trailer']], ['trailer']],
    'unassigned' => [['type' => ['unassigned']], ['orphan']],
    'multiple usages' => [['type' => ['lesson', 'trailer']], ['trailer', 'lesson']],
    'processing' => [['status' => ['processing']], ['trailer']],
]);

it('paginates and sorts videos on the server', function () {
    $videos = Video::factory()->count(26)->create();
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.videos'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('videos.rows', 25)
        ->where('videos.rows.0.key', $videos->last()->id)
    );

    $this->get(route('admin.videos', ['page' => 2]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('videos.rows', 1)
        ->where('videos.rows.0.key', $videos->first()->id)
    );

    $this->get(route('admin.videos', ['sort_by' => 'id', 'sort_direction' => 'asc']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('videos.rows.0.key', $videos->first()->id));
});
