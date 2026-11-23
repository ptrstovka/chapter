<?php

use App\Enums\Preference;
use App\Enums\VideoFormat;
use App\Enums\VideoProcessingStatus;
use App\Facades\Settings;
use App\Jobs\ProcessVideo;
use App\Models\User;
use App\Models\Video;
use App\Support\VideoProcessing;
use App\Support\VideoSettings;
use App\Table\Actions\ConvertVideoToHlsAction;
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
    Settings::set(Preference::EnableVideoHls, true);
    Bus::fake([ProcessVideo::class]);
});

/** @param array<int, int> $ids */
function hlsConversionPayload(array $ids): array
{
    return [
        'action' => ConvertVideoToHlsAction::class,
        'args' => Crypt::encrypt([]),
        'selection' => $ids,
    ];
}

it('offers the HLS action for eligible rows and bulk selection only when enabled', function () {
    $ready = Video::factory()->ready()->create();
    $failed = Video::factory()->create(['processing_status' => VideoProcessingStatus::Failed]);
    $hls = Video::factory()->ready()->create(['hls_playlist_path' => 'video-streams/old/master.m3u8']);
    $withoutOriginal = Video::factory()->ready()->create(['file_path' => null]);
    $queued = Video::factory()->create();
    $processing = Video::factory()->create(['processing_status' => VideoProcessingStatus::Processing]);

    $this->actingAs(User::factory()->admin()->create());
    $response = $this->get(route('admin.videos'))->assertOk();
    $rows = collect($response->inertiaProps('videos.rows'))->keyBy('key');

    foreach ([$ready, $failed] as $video) {
        expect($rows[$video->id]['actions'])->toHaveCount(2)
            ->and($rows[$video->id]['actions'][0])->toMatchArray([
                'label' => __('Convert to HLS'),
                'action' => ConvertVideoToHlsAction::class,
                'canRun' => true,
                'isBulk' => true,
            ]);
    }

    foreach ([$hls, $withoutOriginal, $queued, $processing] as $video) {
        expect($rows[$video->id]['actions'][0]['canRun'])->toBeFalse();
    }

    Settings::set(Preference::EnableVideoHls, false);
    $response = $this->get(route('admin.videos'))->assertOk();

    foreach ($response->inertiaProps('videos.rows') as $row) {
        expect(collect($row['actions'])->pluck('action')->all())->not->toContain(ConvertVideoToHlsAction::class);
    }
});

it('queues a single video using current quality and original retention settings', function () {
    $video = Video::factory()->ready()->create();
    Settings::set(Preference::VideoQualities, [720, 480]);
    Settings::set(Preference::KeepOriginalVideos, false);

    $this->actingAs(User::factory()->admin()->create())
        ->from(route('admin.videos'))
        ->post(route('ui.data-table-action'), hlsConversionPayload([$video->id]))
        ->assertRedirect(route('admin.videos'));

    $video->refresh();
    expect($video->processing_status)->toBe(VideoProcessingStatus::Queued)
        ->and($video->format)->toBe(VideoFormat::Hls)
        ->and($video->processing_options)->toBe(['hls' => true, 'keep_original' => false, 'qualities' => [720, 480]])
        ->and($video->file_path)->not->toBeNull();
    Bus::assertDispatched(ProcessVideo::class, fn (ProcessVideo $job) => $job->videoId === $video->id
        && $job->attempt === $video->processing_attempt
        && $job->queue === config('video.queue')
        && $job->connection === config('video.queue_connection'));
    Bus::assertDispatchedTimes(ProcessVideo::class, 1);
});

it('queues eligible videos in bulk and ignores repeated requests and ineligible IDs', function () {
    $ready = Video::factory()->ready()->create();
    $failed = Video::factory()->create([
        'processing_status' => VideoProcessingStatus::Failed,
        'processing_options' => ['hls' => false, 'keep_original' => false, 'qualities' => [360]],
        'processing_error' => 'Previous failure',
    ]);
    $hls = Video::factory()->ready()->create(['hls_playlist_path' => 'video-streams/old/master.m3u8']);
    $missingSource = Video::factory()->ready()->create(['file_path' => null]);
    $queued = Video::factory()->create();
    $processing = Video::factory()->create(['processing_status' => VideoProcessingStatus::Processing]);
    $untouched = Video::factory()->ready()->create();
    $payload = hlsConversionPayload([$ready->id, $failed->id, $hls->id, $missingSource->id, $queued->id, $processing->id, $ready->id, 999999]);
    $this->actingAs(User::factory()->admin()->create());

    $this->post(route('ui.data-table-action'), $payload)->assertRedirect();
    $readyAttempt = $ready->fresh()->processing_attempt;
    $failedAttempt = $failed->fresh()->processing_attempt;
    $this->post(route('ui.data-table-action'), $payload)->assertRedirect();

    expect($ready->fresh()->processing_attempt)->toBe($readyAttempt)
        ->and($failed->fresh()->processing_attempt)->toBe($failedAttempt)
        ->and($failed->fresh()->processing_status)->toBe(VideoProcessingStatus::Queued)
        ->and($failed->fresh()->processing_options)->toBe(app(VideoSettings::class)->snapshot())
        ->and($failed->fresh()->processing_error)->toBeNull();

    foreach ([$hls, $missingSource, $queued, $processing, $untouched] as $video) {
        expect($video->fresh()->getAttributes())->toMatchArray($video->getAttributes());
    }

    Bus::assertDispatchedTimes(ProcessVideo::class, 2);
    Bus::assertDispatched(ProcessVideo::class, fn (ProcessVideo $job) => $job->videoId === $ready->id);
    Bus::assertDispatched(ProcessVideo::class, fn (ProcessVideo $job) => $job->videoId === $failed->id);
});

it('rejects unauthenticated and non admin action requests', function () {
    $video = Video::factory()->ready()->create();
    $payload = hlsConversionPayload([$video->id]);

    $this->postJson(route('ui.data-table-action'), $payload)->assertUnauthorized();
    $this->actingAs(User::factory()->create())
        ->postJson(route('ui.data-table-action'), $payload)->assertUnauthorized();

    expect($video->fresh()->processing_status)->toBe(VideoProcessingStatus::Ready);
    Bus::assertNothingDispatched();
});

it('rejects a previously rendered action after HLS is disabled', function () {
    $video = Video::factory()->ready()->create();
    $this->actingAs(User::factory()->admin()->create());
    $action = $this->get(route('admin.videos'))->assertOk()->inertiaProps('videos.rows.0.actions.0');
    Settings::set(Preference::EnableVideoHls, false);

    $this->postJson(route('ui.data-table-action'), [
        'action' => $action['action'],
        'args' => $action['args'],
        'selection' => [$video->id],
    ])->assertUnauthorized();

    expect($video->fresh()->processing_status)->toBe(VideoProcessingStatus::Ready);
    Bus::assertNothingDispatched();
});

it('skips locked videos without blocking the rest of a bulk request', function () {
    $locked = Video::factory()->ready()->create();
    $ready = Video::factory()->ready()->create();
    $lock = Cache::lock(VideoProcessing::lockKey($locked->id), 30);
    expect($lock->get())->toBeTrue();

    try {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('ui.data-table-action'), hlsConversionPayload([$locked->id, $ready->id]))
            ->assertRedirect();
    } finally {
        $lock->release();
    }

    expect($locked->fresh()->processing_status)->toBe(VideoProcessingStatus::Ready)
        ->and($ready->fresh()->processing_status)->toBe(VideoProcessingStatus::Queued);
    Bus::assertDispatchedTimes(ProcessVideo::class, 1);
});

it('rechecks eligibility under the processing lock instead of trusting stale models', function (array $changes) {
    $video = Video::factory()->ready()->create();
    Video::query()->whereKey($video->id)->update($changes);

    expect(app(VideoProcessing::class)->queueHlsConversion($video, app(VideoSettings::class)->snapshot()))->toBeFalse()
        ->and($video->fresh()->processing_attempt)->toBeNull();
    Bus::assertNothingDispatched();
})->with([
    'already queued' => [['processing_status' => VideoProcessingStatus::Queued]],
    'already processing' => [['processing_status' => VideoProcessingStatus::Processing]],
    'already HLS' => [['hls_playlist_path' => 'video-streams/new/master.m3u8']],
    'removed original' => [['file_path' => null]],
]);

it('marks a conversion failed and surfaces queue dispatch failures', function () {
    $video = Video::factory()->ready()->create();
    Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Queue unavailable'));

    expect(fn () => app(VideoProcessing::class)->queueHlsConversion($video, app(VideoSettings::class)->snapshot()))
        ->toThrow(RuntimeException::class, 'Queue unavailable');
    expect($video->fresh()->processing_status)->toBe(VideoProcessingStatus::Failed)
        ->and($video->fresh()->processing_error)->toBe('Queue unavailable');
});
