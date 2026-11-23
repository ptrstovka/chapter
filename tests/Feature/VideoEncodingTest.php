<?php

use App\Support\VideoEncoder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

it('creates playable local HLS with relative segments and the expected resolutions', function (bool $audio, string $size, array $qualities, array $expected) {
    $root = storage_path('framework/testing/encode-'.Str::uuid());
    File::ensureDirectoryExists($root);
    $disk = Storage::build(['driver' => 'local', 'root' => $root, 'throw' => true]);
    try {
        $args = [config('laravel-ffmpeg.ffmpeg.binaries'), '-v', 'error', '-f', 'lavfi', '-i', 'testsrc2=size='.$size.':rate=24'];
        if ($audio) {
            array_push($args, '-f', 'lavfi', '-i', 'sine=frequency=440:sample_rate=44100');
        }
        array_push($args, '-t', '1', '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-y', $disk->path('source.mp4'));
        (new Process($args))->setTimeout(30)->mustRun();
        $result = app(VideoEncoder::class)->encode($disk, 'source.mp4', ['hls' => true, 'keep_original' => true, 'qualities' => $qualities]);
        expect($result['duration'])->toBeGreaterThan(0)->and($disk->exists($result['poster']))->toBeTrue();

        $playlist = $disk->get($result['playlist']);
        $variants = array_values(array_filter(explode("\n", $playlist), fn ($line) => $line !== '' && ! str_starts_with($line, '#')));
        expect($variants)->toHaveCount(count($expected));
        $dimensions = [];
        foreach ($variants as $variant) {
            expect($variant)->not->toContain($root);
            $path = 'output/'.$variant;
            foreach (explode("\n", $disk->get($path)) as $line) {
                if ($line !== '' && ! str_starts_with($line, '#')) {
                    expect($disk->exists('output/'.$line))->toBeTrue();
                }
            }
            $probe = new Process([config('laravel-ffmpeg.ffprobe.binaries'), '-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height', '-of', 'json', $disk->path($path)]);
            $probe->mustRun();
            $stream = json_decode($probe->getOutput(), true)['streams'][0];
            $dimensions[] = [$stream['width'], $stream['height']];
        }
        expect($dimensions)->toBe($expected);
    } finally {
        File::deleteDirectory($root);
    }
})->with([
    'two variants with audio' => [true, '1280x720', [360, 720, 1080], [[640, 360], [1280, 720]]],
    'silent low resolution fallback' => [false, '320x180', [1080], [[320, 180]]],
    'portrait' => [false, '360x640', [360, 720], [[360, 640]]],
]);
