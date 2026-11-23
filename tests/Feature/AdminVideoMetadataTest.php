<?php

use App\Enums\Preference;
use App\Facades\Settings;
use App\Models\User;
use App\Models\Video;
use App\Support\VideoProcessing;
use App\Table\Actions\RefreshVideoMetadataAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('public');
    config(['filesystems.content_disk' => 'public']);
});

/** @param array<int, int> $ids */
function videoMetadataPayload(array $ids): array
{
    return ['action' => RefreshVideoMetadataAction::class, 'args' => Crypt::encrypt([]), 'selection' => $ids];
}

it('offers metadata refresh for rows and bulk selection independently of HLS settings', function (bool $hlsEnabled) {
    Settings::set(Preference::EnableVideoHls, $hlsEnabled);
    Video::factory()->ready()->create();
    Video::factory()->ready()->create(['file_path' => null, 'hls_playlist_path' => 'video-streams/current/master.m3u8']);
    Storage::shouldReceive('disk')->never();

    $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.videos'))->assertOk();

    foreach ($response->inertiaProps('videos.rows') as $row) {
        expect(collect($row['actions'])->firstWhere('action', RefreshVideoMetadataAction::class))->toMatchArray([
            'label' => __('Refresh metadata'),
            'canRun' => true,
            'isBulk' => true,
        ]);
    }
})->with([true, false]);

it('refreshes a selected video and shows the updated sizes and totals after the redirect', function () {
    Settings::set(Preference::EnableVideoHls, false);
    $video = Video::factory()->ready()->create(['hls_playlist_path' => 'video-streams/current/master.m3u8']);
    $untouched = Video::factory()->ready()->create();
    Storage::disk('public')->put($video->file_path, 'source video');
    Storage::disk('public')->put($video->hls_playlist_path, '#EXTM3U');
    $before = $video->only(['file_path', 'hls_playlist_path', 'duration_seconds', 'processing_status', 'processing_attempt']);
    Bus::fake();

    $this->actingAs(User::factory()->admin()->create())
        ->from(route('admin.videos'))
        ->post(route('ui.data-table-action'), videoMetadataPayload([$video->id]))
        ->assertRedirect(route('admin.videos'))
        ->assertInertiaFlash('toast.variant', 'default')
        ->assertInertiaFlash('toast.content', null);

    expect($video->fresh()->original_size_bytes)->toBe(12)
        ->and($video->fresh()->hls_size_bytes)->toBe(7)
        ->and($video->fresh()->only(array_keys($before)))->toBe($before)
        ->and($untouched->fresh()->original_size_bytes)->toBeNull();

    $response = $this->get(route('admin.videos'))->assertOk();
    expect($response->inertiaProps('statistics.knownSizeBytes'))->toBe(19);
    Bus::assertNothingDispatched();
});

it('continues a bulk refresh after errors and skips locked videos without changing their data', function () {
    $missing = Video::factory()->ready()->create(['original_size_bytes' => 100]);
    $locked = Video::factory()->ready()->create();
    $ready = Video::factory()->ready()->create();
    $untouched = Video::factory()->ready()->create();
    Storage::disk('public')->put($ready->file_path, 'source video');
    $lock = Cache::lock(VideoProcessing::lockKey($locked->id), 60);
    expect($lock->get())->toBeTrue();

    try {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('ui.data-table-action'), videoMetadataPayload([$missing->id, $locked->id, $ready->id, $ready->id, 999999]))
            ->assertRedirect()
            ->assertInertiaFlash('toast.variant', 'destructive')
            ->assertInertiaFlash('toast.content', __('Failed video IDs: :ids', ['ids' => $missing->id]));
    } finally {
        $lock->release();
    }

    expect($missing->fresh()->original_size_bytes)->toBe(100)
        ->and($locked->fresh()->original_size_bytes)->toBeNull()
        ->and($ready->fresh()->original_size_bytes)->toBe(12)
        ->and($untouched->fresh()->original_size_bytes)->toBeNull();
});

it('rejects unauthenticated and non admin metadata action requests', function () {
    $video = Video::factory()->ready()->create();
    Storage::shouldReceive('disk')->never();
    $payload = videoMetadataPayload([$video->id]);

    $this->postJson(route('ui.data-table-action'), $payload)->assertUnauthorized();
    $this->actingAs(User::factory()->create())->postJson(route('ui.data-table-action'), $payload)->assertUnauthorized();
    $this->actingAs(User::factory()->author()->create())->postJson(route('ui.data-table-action'), $payload)->assertUnauthorized();

    expect($video->fresh()->original_size_bytes)->toBeNull();
});
