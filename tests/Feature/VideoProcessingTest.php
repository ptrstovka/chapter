<?php

use App\Enums\Preference;
use App\Enums\VideoFormat;
use App\Enums\VideoProcessingStatus;
use App\Facades\Settings;
use App\Jobs\DeleteVideoFiles;
use App\Jobs\ProcessVideo;
use App\Models\Video;
use App\Support\VideoEncoder;
use App\Support\VideoProcessing;
use App\View\Models\VideoSource;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    config(['filesystems.content_disk' => 'public', 'video.work_directory' => storage_path('framework/testing/video-work')]);
});

function fakeVideoEncoder(): void
{
    app()->instance(VideoEncoder::class, new class extends VideoEncoder
    {
        public function encode(FilesystemAdapter $work, string $source, array $options): array
        {
            expect($work->get($source))->toBe('source video');
            $work->put('poster.jpg', 'poster');
            if ($options['hls']) {
                $work->put('output/master.m3u8', "#EXTM3U\nvariant.m3u8");
                $work->put('output/variant.m3u8', "#EXTM3U\nsegment.ts");
                $work->put('output/segment.ts', 'encoded segment');
            }

            return ['duration' => 42, 'poster' => 'poster.jpg', 'playlist' => $options['hls'] ? 'output/master.m3u8' : null];
        }
    });
}

it('prepares HLS and deletes the original only after activating the complete output', function () {
    fakeVideoEncoder();
    $video = Video::factory()->create();
    Storage::disk('public')->put($video->file_path, 'source video');
    $original = $video->file_path;
    $processing = app(VideoProcessing::class);
    $attempt = $processing->prepare($video, ['hls' => true, 'keep_original' => false, 'qualities' => [1080]]);
    expect(VideoSource::for($video->fresh())->toView()['url'])->toBeNull();
    $processing->process($video->id, $attempt);

    $video->refresh();
    expect($video->isReady())->toBeTrue()
        ->and($video->format)->toBe(VideoFormat::Hls)
        ->and($video->file_path)->toBeNull()
        ->and($video->original_size_bytes)->toBeNull()
        ->and($video->duration_seconds)->toBe(42)
        ->and($video->hls_size_bytes)->toBe(strlen("#EXTM3U\nvariant.m3u8") + strlen("#EXTM3U\nsegment.ts") + strlen('encoded segment'));
    Storage::disk('public')->assertMissing($original);
    Storage::disk('public')->assertExists([$video->hls_playlist_path, dirname($video->hls_playlist_path).'/segment.ts']);
    expect(File::directories(config('video.work_directory')))->toBe([]);
});

it('always keeps the original when HLS is disabled', function () {
    fakeVideoEncoder();
    $video = Video::factory()->create();
    Storage::disk('public')->put($video->file_path, 'source video');
    $processing = app(VideoProcessing::class);
    $processing->process($video->id, $processing->prepare($video, ['hls' => false, 'keep_original' => false, 'qualities' => [1080]]));
    expect($video->fresh()->isReady())->toBeTrue()
        ->and($video->fresh()->original_size_bytes)->toBe(strlen('source video'))
        ->and($video->fresh()->hls_playlist_path)->toBeNull()
        ->and($video->fresh()->hls_size_bytes)->toBeNull();
    Storage::disk('public')->assertExists($video->file_path);
});

it('keeps the source and previous HLS when encoding fails', function () {
    $video = Video::factory()->ready()->create([
        'hls_playlist_path' => 'video-streams/old/master.m3u8',
        'hls_size_bytes' => 123456,
        'format' => VideoFormat::Hls,
    ]);
    Storage::disk('public')->put($video->file_path, 'source video');
    Storage::disk('public')->put($video->hls_playlist_path, '#EXTM3U');
    $this->mock(VideoEncoder::class)->shouldReceive('encode')->once()->andThrow(new RuntimeException('Encoding failed'));
    $processing = app(VideoProcessing::class);
    $attempt = $processing->prepare($video, ['hls' => true, 'keep_original' => false, 'qualities' => [720]]);
    expect(fn () => $processing->process($video->id, $attempt))->toThrow(RuntimeException::class, 'Encoding failed');
    expect($video->fresh()->processing_status)->toBe(VideoProcessingStatus::Failed)
        ->and($video->fresh()->hls_size_bytes)->toBe(123456)
        ->and($video->fresh()->getUrl())->toContain('video-streams/old/master.m3u8');
    Storage::disk('public')->assertExists([$video->file_path, $video->hls_playlist_path]);
});

it('rejects an unsuccessful destination write before marking the video ready', function () {
    fakeVideoEncoder();
    $video = Video::factory()->create();
    Storage::disk('public')->put($video->file_path, 'source video');
    $processing = new class(app(VideoEncoder::class)) extends VideoProcessing
    {
        public function copy(FilesystemAdapter $from, string $source, FilesystemAdapter $to, string $destination, array $options = []): void
        {
            if ($to === Storage::disk('public') && str_ends_with($destination, '.ts')) {
                throw new RuntimeException('Unable to write');
            }
            parent::copy($from, $source, $to, $destination, $options);
        }
    };
    $attempt = $processing->prepare($video, ['hls' => true, 'keep_original' => false, 'qualities' => [1080]]);
    expect(fn () => $processing->process($video->id, $attempt))->toThrow(RuntimeException::class);
    expect($video->fresh()->processing_status)->toBe(VideoProcessingStatus::Failed)
        ->and($video->fresh()->hls_size_bytes)->toBeNull();
    Storage::disk('public')->assertExists($video->file_path);
});

it('ignores stale attempts without modifying a newer generation', function () {
    $video = Video::factory()->create([
        'hls_playlist_path' => 'video-streams/old/master.m3u8',
        'hls_size_bytes' => 123456,
    ]);
    $processing = app(VideoProcessing::class);
    $old = $processing->prepare($video, ['hls' => true, 'keep_original' => true, 'qualities' => [1080]]);
    $current = $processing->prepare($video, ['hls' => true, 'keep_original' => true, 'qualities' => [720]]);
    $processing->process($video->id, $old);
    expect($video->fresh()->processing_attempt)->toBe($current)
        ->and($video->fresh()->hls_size_bytes)->toBe(123456)
        ->and($video->fresh()->processing_status)->toBe(VideoProcessingStatus::Queued);
});

it('updates the recorded HLS size only when activating a new stream', function (bool $hls) {
    fakeVideoEncoder();
    $video = Video::factory()->ready()->create([
        'format' => VideoFormat::Hls,
        'hls_playlist_path' => 'video-streams/old/master.m3u8',
        'hls_size_bytes' => 123456,
    ]);
    Storage::disk('public')->put($video->file_path, 'source video');
    Storage::disk('public')->put($video->hls_playlist_path, '#EXTM3U');
    $processing = app(VideoProcessing::class);
    $attempt = $processing->prepare($video, ['hls' => $hls, 'keep_original' => true, 'qualities' => [720]]);

    expect($video->fresh()->hls_size_bytes)->toBe(123456);

    $processing->process($video->id, $attempt);

    expect($video->fresh()->hls_size_bytes)->toBe($hls
        ? strlen("#EXTM3U\nvariant.m3u8") + strlen("#EXTM3U\nsegment.ts") + strlen('encoded segment')
        : 123456)
        ->and($video->fresh()->original_size_bytes)->toBe(strlen('source video'))
        ->and($video->fresh()->hls_playlist_path)->toBe($hls
            ? 'video-streams/'.$video->id.'/'.$attempt.'/master.m3u8'
            : 'video-streams/old/master.m3u8');
})->with([true, false]);

it('does not resurrect a video deleted while encoding', function () {
    $video = Video::factory()->create();
    Storage::disk('public')->put($video->file_path, 'source video');
    $this->mock(VideoEncoder::class)->shouldReceive('encode')->andReturnUsing(function ($work) use ($video) {
        $video->delete();
        $work->put('poster.jpg', 'poster');
        $work->put('output/master.m3u8', '#EXTM3U');

        return ['duration' => 42, 'poster' => 'poster.jpg', 'playlist' => 'output/master.m3u8'];
    });
    $processing = app(VideoProcessing::class);
    $processing->process($video->id, $processing->prepare($video, ['hls' => true, 'keep_original' => false, 'qualities' => [1080]]));
    expect(Video::find($video->id))->toBeNull();
    Storage::disk('public')->assertDirectoryEmpty('video-streams/'.$video->id);
});

it('keeps ready playback independent of global HLS settings', function () {
    $video = Video::factory()->ready()->create();
    Settings::set(Preference::EnableVideoHls, true);
    expect(VideoSource::for($video)->toView()['type'])->toBe('video/mp4')
        ->and(VideoSource::for($video)->toView()['url'])->toEndWith('.mp4')
        ->and(VideoSource::for(null))->toBeNull();
});

it('marks a video failed when dispatching its worker fails', function () {
    $video = Video::factory()->create();
    $processing = app(VideoProcessing::class);
    $attempt = $processing->prepare($video, ['hls' => true, 'keep_original' => true, 'qualities' => [720]]);
    Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Queue unavailable'));

    expect(fn () => $processing->dispatch($video, $attempt))->toThrow(RuntimeException::class, 'Queue unavailable');
    expect($video->fresh()->processing_status)->toBe(VideoProcessingStatus::Failed);
});

it('keeps a retrying worker queued until its final processing failure', function () {
    $video = Video::factory()->create();
    Storage::disk('public')->put($video->file_path, 'source video');
    $this->mock(VideoEncoder::class)->shouldReceive('encode')->twice()->andThrow(new RuntimeException('Encoding failed'));
    $processing = app(VideoProcessing::class);
    $attempt = $processing->prepare($video, ['hls' => true, 'keep_original' => true, 'qualities' => [720]]);
    $job = new ProcessVideo($video->id, $attempt);
    $queueJob = Mockery::mock(Job::class);
    $queueJob->shouldReceive('attempts')->andReturn(1, 2);
    $job->setJob($queueJob);

    expect(fn () => $job->handle($processing))->toThrow(RuntimeException::class);
    expect($video->fresh()->processing_status)->toBe(VideoProcessingStatus::Queued);
    expect(fn () => $job->handle($processing))->toThrow(RuntimeException::class);
    expect($video->fresh()->processing_status)->toBe(VideoProcessingStatus::Failed);
});

it('cleans generated posters even when deletion captured an older video record', function () {
    fakeVideoEncoder();
    $video = Video::factory()->create();
    Storage::disk('public')->put($video->file_path, 'source video');
    $deletion = new DeleteVideoFiles('public', $video->id, $video->file_path, $video->poster_image_file_path);
    $processing = app(VideoProcessing::class);
    $processing->process($video->id, $processing->prepare($video, ['hls' => true, 'keep_original' => true, 'qualities' => [720]]));
    $poster = $video->fresh()->poster_image_file_path;
    Storage::disk('public')->assertExists($poster);

    $deletion->handle();

    Storage::disk('public')->assertMissing([$video->file_path, $poster]);
    Storage::disk('public')->assertDirectoryEmpty('video-streams/'.$video->id);
});

it('processes selected existing videos and reports missing originals', function () {
    fakeVideoEncoder();
    Settings::set(Preference::EnableVideoHls, true);
    $video = Video::factory()->ready()->create();
    Storage::disk('public')->put($video->file_path, 'source video');
    $this->artisan('videos:process', ['--video' => [$video->id]])->assertSuccessful();
    expect($video->fresh()->hls_playlist_path)->not->toBeNull();
    $this->artisan('videos:process', ['--video' => [$video->id]])->expectsOutput('Completed: 0; skipped: 1; failed: 0.')->assertSuccessful();
    $video->update(['file_path' => null]);
    $this->artisan('videos:process', ['--video' => [$video->id], '--reprocess' => true])->assertFailed();
});
