<?php

use App\Models\Video;
use App\Support\VideoMetadata;
use App\Support\VideoProcessing;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use Mockery\MockInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    config(['filesystems.content_disk' => 'public']);
});

function mockVideoMetadataFilesystem(): MockInterface
{
    $filesystem = Mockery::mock(FilesystemOperator::class);
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('getDriver')->andReturn($filesystem);
    Storage::shouldReceive('disk')->with('public')->andReturn($disk);

    return $filesystem;
}

it('refreshes every video from its original and active HLS directory', function () {
    $video = Video::factory()->ready()->create(['hls_playlist_path' => 'video-streams/one/current/master.m3u8']);
    $originalOnly = Video::factory()->ready()->create(['hls_size_bytes' => 999]);
    $hlsOnly = Video::factory()->ready()->create([
        'file_path' => null, 'original_size_bytes' => 999,
        'hls_playlist_path' => 'video-streams/two/current/master.m3u8',
    ]);
    $disk = Storage::disk('public');
    $disk->put($video->file_path, 'source video');
    $disk->put($originalOnly->file_path, '');
    $disk->put($video->hls_playlist_path, '#EXTM3U');
    $disk->put('video-streams/one/current/720p/playlist.m3u8', '#EXTM3U');
    $disk->put('video-streams/one/current/720p/segment.ts', 'segment');
    $disk->put('video-streams/one/old/segment.ts', 'must not be counted');
    $disk->put($hlsOnly->hls_playlist_path, '#EXTM3U');
    $before = $video->only(['file_path', 'hls_playlist_path', 'duration_seconds', 'processing_status', 'processing_attempt']);
    Bus::fake();

    $this->artisan('videos:refresh-metadata')
        ->expectsOutput('Refreshed: 3; skipped: 0; failed: 0.')->assertSuccessful();

    expect($video->fresh()->original_size_bytes)->toBe(12)
        ->and($video->fresh()->hls_size_bytes)->toBe(21)
        ->and($video->fresh()->only(array_keys($before)))->toBe($before)
        ->and($originalOnly->fresh()->original_size_bytes)->toBe(0)
        ->and($originalOnly->fresh()->hls_size_bytes)->toBeNull()
        ->and($hlsOnly->fresh()->original_size_bytes)->toBeNull()
        ->and($hlsOnly->fresh()->hls_size_bytes)->toBe(7);
    $disk->assertExists([$video->file_path, $video->hls_playlist_path]);
    Bus::assertNothingDispatched();

    $disk->put($video->file_path, 'changed source');
    $this->artisan('videos:refresh-metadata', ['--video' => [$video->id]])->assertSuccessful();
    expect($video->fresh()->original_size_bytes)->toBe(14);
});

it('refreshes selected IDs and displays progress for only those videos', function () {
    $videos = Video::factory()->ready()->count(3)->create();
    foreach ($videos as $video) {
        Storage::disk('public')->put($video->file_path, 'source video');
    }
    $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);

    $exitCode = Artisan::call('videos:refresh-metadata', ['--video' => [$videos[0]->id, $videos[2]->id]], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toContain('0/2', '1/2', '2/2', '50%', '100%', 'Video #'.$videos[2]->id, 'Refreshed: 2; skipped: 0; failed: 0.')
        ->and($videos[0]->fresh()->original_size_bytes)->toBe(12)
        ->and($videos[1]->fresh()->original_size_bytes)->toBeNull()
        ->and($videos[2]->fresh()->original_size_bytes)->toBe(12);
});

it('uses storage metadata for large sizes without downloading files or probing every segment', function () {
    $video = Video::factory()->ready()->create(['hls_playlist_path' => 'video-streams/current/master.m3u8']);
    $filesystem = mockVideoMetadataFilesystem();
    $filesystem->shouldReceive('fileSize')->once()->with($video->file_path)->andReturn(5368709120);
    $filesystem->shouldReceive('listContents')->once()->with('video-streams/current', true)->andReturn(new DirectoryListing([
        new DirectoryAttributes('video-streams/current/720p'),
        new FileAttributes($video->hls_playlist_path, 100),
        new FileAttributes('video-streams/current/720p/segment.ts', 4294967296),
    ]));

    $this->artisan('videos:refresh-metadata')->assertSuccessful();

    expect($video->fresh()->original_size_bytes)->toBe(5368709120)
        ->and($video->fresh()->hls_size_bytes)->toBe(4294967396);
});

it('preserves previous metadata on a storage error and continues with other videos', function () {
    $missing = Video::factory()->ready()->create(['original_size_bytes' => 100, 'hls_size_bytes' => 200]);
    $existing = Video::factory()->ready()->create();
    Storage::disk('public')->put($existing->file_path, 'source video');

    $this->artisan('videos:refresh-metadata')
        ->expectsOutput('Refreshed: 1; skipped: 0; failed: 1.')->assertFailed();

    expect($missing->fresh()->original_size_bytes)->toBe(100)
        ->and($missing->fresh()->hls_size_bytes)->toBe(200)
        ->and($missing->fresh()->file_path)->toBe($missing->file_path)
        ->and($existing->fresh()->original_size_bytes)->toBe(12);
});

it('does not replace known sizes with an incomplete HLS listing', function (bool $interrupted) {
    $video = Video::factory()->ready()->create([
        'original_size_bytes' => 100, 'hls_size_bytes' => 200,
        'hls_playlist_path' => 'video-streams/current/master.m3u8',
    ]);
    $filesystem = mockVideoMetadataFilesystem();
    $filesystem->shouldReceive('fileSize')->once()->with($video->file_path)->andReturn(12);
    $listing = (function () use ($interrupted, $video) {
        yield new FileAttributes('video-streams/current/segment.ts', 5);
        if ($interrupted) {
            yield new FileAttributes($video->hls_playlist_path, 7);
            throw new RuntimeException('Listing interrupted');
        }
    })();
    $filesystem->shouldReceive('listContents')->once()->with('video-streams/current', true)->andReturn(new DirectoryListing($listing));

    $this->artisan('videos:refresh-metadata')->assertFailed();

    expect($video->fresh()->original_size_bytes)->toBe(100)
        ->and($video->fresh()->hls_size_bytes)->toBe(200);
})->with([false, true]);

it('refuses to scan the storage root as an HLS directory', function () {
    $video = Video::factory()->ready()->create(['file_path' => null, 'hls_playlist_path' => 'master.m3u8', 'hls_size_bytes' => 200]);
    mockVideoMetadataFilesystem()->shouldNotReceive('listContents');

    $this->artisan('videos:refresh-metadata')->assertFailed();

    expect($video->fresh()->hls_size_bytes)->toBe(200);
});

it('skips a video while its processing lock is held', function () {
    $video = Video::factory()->ready()->create();
    $lock = Cache::lock(VideoProcessing::lockKey($video->id), 60);
    expect($lock->get())->toBeTrue();
    Storage::shouldReceive('disk')->never();

    try {
        $this->artisan('videos:refresh-metadata')
            ->expectsOutput('Refreshed: 0; skipped: 1; failed: 0.')->assertSuccessful();
        expect($video->fresh()->original_size_bytes)->toBeNull();
    } finally {
        $lock->release();
    }
});

it('does not save stale metadata when the video changes during retrieval', function () {
    $video = Video::factory()->ready()->create(['original_size_bytes' => 100]);
    $filesystem = mockVideoMetadataFilesystem();
    $filesystem->shouldReceive('fileSize')->once()->with($video->file_path)->andReturnUsing(function () use ($video) {
        $video->update(['file_path' => 'course-videos/replacement.mp4', 'original_size_bytes' => 999]);

        return 12;
    });

    expect(app(VideoMetadata::class)->refresh($video))->toBeFalse()
        ->and($video->fresh()->original_size_bytes)->toBe(999);
});

it('validates all selected IDs before updating any metadata', function (string $invalidId) {
    $video = Video::factory()->ready()->create();
    Storage::shouldReceive('disk')->never();

    $this->artisan('videos:refresh-metadata', ['--video' => [$video->id, $invalidId]])
        ->expectsOutput('Unknown video ID: '.$invalidId)->assertFailed();
    expect($video->fresh()->original_size_bytes)->toBeNull();
})->with(['invalid', '999999']);

it('handles an empty video library', function () {
    $this->artisan('videos:refresh-metadata')->expectsOutput('No videos to refresh.')->assertSuccessful();
});
