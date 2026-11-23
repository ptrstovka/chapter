<?php

use App\Enums\Preference;
use App\Facades\Settings;
use App\Models\Video;
use App\Support\VideoProcessing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

uses(RefreshDatabase::class);

it('advances video progress through completed skipped and failed videos', function (bool $decorated) {
    Settings::set(Preference::EnableVideoHls, true);
    $first = Video::factory()->ready()->create();
    $skipped = Video::factory()->ready()->create(['hls_playlist_path' => 'video-streams/existing/master.m3u8']);
    $failed = Video::factory()->ready()->create();
    $last = Video::factory()->ready()->create();
    $processing = $this->mock(VideoProcessing::class);
    $processing->shouldReceive('prepare')->times(3)->andReturnUsing(function (Video $video) use ($skipped) {
        expect($video->id)->not->toBe($skipped->id);

        return 'attempt-'.$video->id;
    });
    $processing->shouldReceive('process')->with($first->id, 'attempt-'.$first->id)->once();
    $processing->shouldReceive('process')->with($failed->id, 'attempt-'.$failed->id)->once()->andThrow(new RuntimeException('Encoding failed'));
    $processing->shouldReceive('process')->with($last->id, 'attempt-'.$last->id)->once();
    $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, $decorated);

    $exitCode = Artisan::call('videos:process', [], $output);
    $rendered = $output->fetch();

    expect($exitCode)->toBe(1)
        ->and($rendered)->toContain('Completed: 2; skipped: 1; failed: 1.', 'Video '.$failed->id.': Encoding failed')
        ->not->toContain('Processing video ', ' ready.');

    if ($decorated) {
        expect($rendered)->toContain('0/4', '1/4', '2/4', '3/4', '4/4', '25%', '50%', '75%', '100%', 'Video #'.$last->id, 'Finished with errors.');
    } else {
        expect($rendered)->not->toContain('Video #', "\e[", '100%');
    }
})->with([true, false]);

it('uses only the selected videos for the progress total', function () {
    Settings::set(Preference::EnableVideoHls, true);
    Video::factory()->ready()->create();
    $selected = Video::factory()->ready()->create();
    $processing = $this->mock(VideoProcessing::class);
    $processing->shouldReceive('prepare')->once()->withArgs(fn (Video $video, array $options) => $video->is($selected) && $options['hls'])->andReturn('attempt');
    $processing->shouldReceive('process')->once()->with($selected->id, 'attempt');
    $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);

    $exitCode = Artisan::call('videos:process', ['--video' => [$selected->id]], $output);

    expect($exitCode)->toBe(0)
        ->and($output->fetch())->toContain('0/1', '1/1', '100%', 'Video #'.$selected->id, 'Finished.', 'Completed: 1; skipped: 0; failed: 0.');
});

it('finishes an empty video selection without an indeterminate progress bar', function () {
    $this->mock(VideoProcessing::class)->shouldNotReceive('prepare');
    $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);

    expect(Artisan::call('videos:process', [], $output))->toBe(0)
        ->and($output->fetch())->toContain('No videos to process.')->not->toContain('0/0', '100%');
});

it('rejects an invalid video before starting progress', function (string $id) {
    $this->mock(VideoProcessing::class)->shouldNotReceive('prepare');
    $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);

    expect(Artisan::call('videos:process', ['--video' => [$id]], $output))->toBe(1)
        ->and($output->fetch())->toContain('Unknown video ID: '.$id)->not->toContain('Starting…', '100%');
})->with(['invalid', '999999']);
