<?php

use App\Enums\VideoFormat;
use App\Enums\VideoProcessingStatus;
use App\Enums\VideoUploadStatus;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->admin()->create());
});

it('summarizes every video without reading storage or multiplying upload records', function () {
    $originalAndHls = Video::factory()->ready()->create([
        'duration_seconds' => 90,
        'hls_playlist_path' => 'video-streams/one/master.m3u8',
        'hls_size_bytes' => 1024,
    ]);
    VideoUpload::factory()->for($originalAndHls)->create(['status' => VideoUploadStatus::Completed, 'size' => 4096]);
    VideoUpload::factory()->for($originalAndHls)->create(['status' => VideoUploadStatus::Completed, 'size' => 2048]);
    VideoUpload::factory()->for($originalAndHls)->create(['status' => VideoUploadStatus::Pending, 'size' => 999999]);
    VideoUpload::factory()->for($originalAndHls)->create(['status' => VideoUploadStatus::Cancelled, 'size' => 999999]);

    Video::factory()->ready()->create(['duration_seconds' => 30]);
    $hlsOnly = Video::factory()->create([
        'duration_seconds' => 61,
        'file_path' => null,
        'hls_playlist_path' => 'video-streams/two/master.m3u8',
        'hls_size_bytes' => 4294967296,
        'processing_status' => VideoProcessingStatus::Failed,
    ]);
    VideoUpload::factory()->for($hlsOnly)->create(['status' => VideoUploadStatus::Completed, 'size' => 999999]);
    Video::factory()->create(['format' => VideoFormat::Hls, 'file_path' => '', 'hls_size_bytes' => 999999]);
    Video::factory()->create(['processing_status' => VideoProcessingStatus::Processing]);
    Video::factory()->ready()->create(['duration_seconds' => 0, 'file_path' => null]);
    Video::factory()->ready()->create([
        'duration_seconds' => null,
        'file_path' => null,
        'hls_playlist_path' => 'video-streams/legacy/master.m3u8',
    ]);

    Storage::shouldReceive('disk')->never();

    $response = $this->get(route('admin.videos'))->assertOk();

    expect($response->inertiaProps('statistics'))->toMatchArray([
        'total' => 7,
        'durationSeconds' => 181,
        'unknownDurationCount' => 3,
        'hlsCount' => 3,
        'originalCount' => 3,
        'readyCount' => 4,
        'queuedCount' => 1,
        'processingCount' => 1,
        'failedCount' => 1,
        'originalBytes' => 2048,
        'hlsBytes' => 4294968320,
        'knownSizeBytes' => 4294970368,
        'unknownOriginalSizeCount' => 2,
        'unknownHlsSizeCount' => 1,
    ])->and($response->inertiaProps('statistics.labels.size'))->toBe(Number::fileSize(4294970368));

    $this->get(route('admin.videos', ['hls' => ['no'], 'search' => (string) $originalAndHls->id]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('videos.rows', 0)
        ->where('statistics', $response->inertiaProps('statistics'))
        );
});

it('formats the total duration with hours minutes and seconds across all table pages', function () {
    Video::factory()->count(25)->create(['duration_seconds' => 15, 'file_path' => null]);
    Video::factory()->create(['duration_seconds' => 29899, 'file_path' => null]);

    $this->get(route('admin.videos', ['page' => 2]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('videos.rows', 1)
        ->where('statistics.total', 26)
        ->where('statistics.durationSeconds', 30274)
        ->where('statistics.labels.duration', '8h 24m 34s')
    );
});

it('distinguishes entirely unknown sizes from an empty video library', function () {
    $empty = $this->get(route('admin.videos'))->assertOk();

    expect($empty->inertiaProps('statistics'))->toMatchArray([
        'total' => 0,
        'durationSeconds' => 0,
        'unknownDurationCount' => 0,
        'knownSizeBytes' => 0,
        'unknownOriginalSizeCount' => 0,
        'unknownHlsSizeCount' => 0,
    ])->and($empty->inertiaProps('statistics.labels.size'))->toBe(Number::fileSize(0))
        ->and($empty->inertiaProps('statistics.labels.duration'))->toBe('0s')
        ->and($empty->inertiaProps('statistics.labels.hlsPercentage'))->toBe(Number::percentage(0, maxPrecision: 1, locale: app()->getLocale()));

    Video::factory()->create(['hls_playlist_path' => 'video-streams/legacy/master.m3u8']);

    $this->get(route('admin.videos'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('statistics.total', 1)
        ->where('statistics.unknownDurationCount', 1)
        ->where('statistics.unknownOriginalSizeCount', 1)
        ->where('statistics.unknownHlsSizeCount', 1)
        ->where('statistics.labels.size', __('Unknown'))
        ->where('statistics.labels.originals', __('Unknown'))
        ->where('statistics.labels.hls', __('Unknown'))
    );
});
