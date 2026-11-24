<?php

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\UnableToListContents;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

beforeEach(function () {
    config([
        'filesystems.disks.copy-source' => ['driver' => 'local'],
        'filesystems.disks.copy-destination' => ['driver' => 'local'],
    ]);

    $this->sourceDisk = Storage::fake('copy-source', ['driver' => 'local']);
    $this->destinationDisk = Storage::fake('copy-destination', ['driver' => 'local']);
});

it('advances progress through copied skipped and failed files without per file logs', function (bool $decorated) {
    $files = [
        'course-videos/first.mp4' => 'first',
        'course-videos/skipped.mp4' => 'skipped',
        'course-videos/failed.mp4' => 'failed',
        'video-streams/master.m3u8' => 'playlist',
    ];
    foreach ($files as $path => $contents) {
        $this->sourceDisk->put($path, $contents);
    }
    $this->destinationDisk->put('course-videos/skipped.mp4', 'skipped');
    $destination = Mockery::mock(FilesystemAdapter::class, [
        $this->destinationDisk->getDriver(),
        $this->destinationDisk->getAdapter(),
        $this->destinationDisk->getConfig(),
    ])->makePartial();
    $destination->shouldReceive('writeStream')->times(3)->andReturnUsing(function ($path, $stream, $options) {
        if ($path === 'course-videos/failed.mp4') {
            throw new RuntimeException('Write failed.');
        }

        return $this->destinationDisk->writeStream($path, $stream, $options);
    });
    Storage::set('copy-destination', $destination);
    $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, $decorated);

    $exitCode = Artisan::call('storage:copy', ['source' => 'copy-source', 'destination' => 'copy-destination'], $output);
    $rendered = $output->fetch();

    expect($exitCode)->toBe(1)
        ->and($rendered)->toContain('Copied: 2; skipped: 1; failed: 1.', 'Write failed.')
        ->not->toContain('Checking ', 'Skipped file with matching size:', 'Copying:', 'Copied: course-videos/');
    expect($this->destinationDisk->get('course-videos/first.mp4'))->toBe('first')
        ->and($this->destinationDisk->get('course-videos/skipped.mp4'))->toBe('skipped')
        ->and($this->destinationDisk->get('video-streams/master.m3u8'))->toBe('playlist');
    $this->destinationDisk->assertMissing('course-videos/failed.mp4');

    if ($decorated) {
        expect($rendered)->toContain('0/4', '1/4', '2/4', '3/4', '4/4', '25%', '50%', '75%', '100%', 'File: video-streams/master.m3u8', 'Finished with errors.');
    } else {
        expect($rendered)->not->toContain('File:', "\e[", '100%');
    }
})->with([true, false]);

it('finishes an empty source without an indeterminate progress bar', function () {
    $this->sourceDisk->makeDirectory('empty-directory');
    $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);

    expect(Artisan::call('storage:copy', ['source' => 'copy-source', 'destination' => 'copy-destination'], $output))->toBe(0)
        ->and($output->fetch())->toContain('No files to copy.')->not->toContain('0/0', '100%');
    $this->destinationDisk->assertDirectoryEmpty('/');
});

it('keeps partial progress when listing fails after copying has started', function () {
    $this->sourceDisk->put('first.mp4', 'first');
    $this->sourceDisk->put('second.mp4', 'second');
    $filesystem = Mockery::mock(Filesystem::class, [$this->sourceDisk->getAdapter()])->makePartial();
    $filesystem->shouldReceive('listContents')->with('', true)->once()->andReturn(new DirectoryListing([
        new FileAttributes('first.mp4'),
        new FileAttributes('second.mp4'),
    ]));
    $filesystem->shouldReceive('listContents')->with('', true)->once()->andReturnUsing(function () {
        return new DirectoryListing((function () {
            yield new FileAttributes('first.mp4');

            throw UnableToListContents::atLocation('', true, new RuntimeException('Listing failed'));
        })());
    });
    Storage::set('copy-source', new FilesystemAdapter($filesystem, $this->sourceDisk->getAdapter(), $this->sourceDisk->getConfig()));
    $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);

    expect(Artisan::call('storage:copy', ['source' => 'copy-source', 'destination' => 'copy-destination'], $output))->toBe(1)
        ->and($output->fetch())->toContain('1/2', '50%', 'Interrupted while listing files.', 'Unable to finish listing [copy-source]', 'Copied: 1; skipped: 0; failed: 1.')
        ->not->toContain('100%', 'Finished.');
    expect($this->destinationDisk->get('first.mp4'))->toBe('first');
    $this->destinationDisk->assertMissing('second.mp4');
});

it('copies all files with their original paths and bytes while retaining source and unrelated destination files', function () {
    $files = [
        'course-videos/nested/lekcia 01.mp4' => "video\0\xff\x01content",
        'video-posters/poster.jpeg' => 'poster',
        'course-resources/attachment-without-extension' => '%PDF-1.7 attachment',
        'course-covers/obrázok.png' => 'cover',
        '.hidden' => 'hidden',
        'empty.txt' => '',
    ];

    foreach ($files as $path => $contents) {
        $this->sourceDisk->put($path, $contents);
    }

    $this->destinationDisk->put('unrelated.txt', 'keep');
    $contentDisk = config('filesystems.content_disk');

    $this->artisan('storage:copy copy-source copy-destination')
        ->expectsOutput('Copied: 6; skipped: 0; failed: 0.')
        ->assertSuccessful();

    foreach ($files as $path => $contents) {
        expect($this->destinationDisk->get($path))->toBe($contents)
            ->and($this->sourceDisk->get($path))->toBe($contents);
    }

    expect($this->destinationDisk->get('unrelated.txt'))->toBe('keep')
        ->and(config('filesystems.content_disk'))->toBe($contentDisk);
});

it('skips destination files with matching sizes without reading their contents', function (string $existing) {
    $this->sourceDisk->put('video.mp4', 'original');
    $this->destinationDisk->put('video.mp4', $existing);

    $source = Mockery::mock(FilesystemAdapter::class, [
        $this->sourceDisk->getDriver(),
        $this->sourceDisk->getAdapter(),
        $this->sourceDisk->getConfig(),
    ])->makePartial();
    $source->shouldNotReceive('checksum');
    $source->shouldNotReceive('readStream');
    Storage::set('copy-source', $source);

    $destination = Mockery::mock(FilesystemAdapter::class, [
        $this->destinationDisk->getDriver(),
        $this->destinationDisk->getAdapter(),
        $this->destinationDisk->getConfig(),
    ])->makePartial();
    $destination->shouldNotReceive('writeStream');
    $destination->shouldNotReceive('checksum');
    $destination->shouldNotReceive('readStream');
    $destination->shouldNotReceive('get');
    Storage::set('copy-destination', $destination);

    $this->artisan('storage:copy copy-source copy-destination')
        ->expectsOutput('Copied: 0; skipped: 1; failed: 0.')
        ->assertSuccessful();

    expect($this->destinationDisk->get('video.mp4'))->toBe($existing);
})->with(['same contents' => 'original', 'different contents' => 'modified']);

it('replaces destination files whose sizes differ from the source', function (string $existing) {
    $this->sourceDisk->put('video.mp4', 'original');
    $this->destinationDisk->put('video.mp4', $existing);

    $this->artisan('storage:copy copy-source copy-destination')
        ->expectsOutput('Copied: 1; skipped: 0; failed: 0.')
        ->assertSuccessful();

    expect($this->destinationDisk->get('video.mp4'))->toBe('original')
        ->and($this->sourceDisk->get('video.mp4'))->toBe('original');
})->with(['partial copy' => 'part', 'larger file' => 'longer contents']);

it('copies with one source stream and no checksum or destination content reads', function () {
    $this->sourceDisk->put('video.mp4', 'original');
    $source = Mockery::mock(FilesystemAdapter::class, [
        $this->sourceDisk->getDriver(),
        $this->sourceDisk->getAdapter(),
        $this->sourceDisk->getConfig(),
    ])->makePartial();
    $source->shouldReceive('readStream')->once()->with('video.mp4')->passthru();
    $source->shouldNotReceive('checksum');
    $source->shouldNotReceive('get');
    Storage::set('copy-source', $source);

    $destination = Mockery::mock(FilesystemAdapter::class, [
        $this->destinationDisk->getDriver(),
        $this->destinationDisk->getAdapter(),
        $this->destinationDisk->getConfig(),
    ])->makePartial();
    $destination->shouldNotReceive('checksum');
    $destination->shouldNotReceive('readStream');
    $destination->shouldNotReceive('get');
    Storage::set('copy-destination', $destination);

    $this->artisan('storage:copy copy-source copy-destination')
        ->expectsOutput('Copied: 1; skipped: 0; failed: 0.')
        ->assertSuccessful();

    $this->artisan('storage:copy copy-source copy-destination')
        ->expectsOutput('Copied: 0; skipped: 1; failed: 0.')
        ->assertSuccessful();

    expect($this->destinationDisk->get('video.mp4'))->toBe('original');
});

it('rejects unknown or identical disks before copying', function (string $source, string $destination) {
    $this->sourceDisk->put('keep.txt', 'keep');

    $this->artisan('storage:copy', ['source' => $source, 'destination' => $destination])
        ->assertFailed();

    expect($this->sourceDisk->get('keep.txt'))->toBe('keep');
    $this->destinationDisk->assertDirectoryEmpty('/');
})->with([
    'same disk' => ['copy-source', 'copy-source'],
    'unknown source' => ['missing', 'copy-destination'],
    'unknown destination' => ['copy-source', 'missing'],
]);

it('rejects aliased and nested local storage locations', function (string $suffix) {
    config(['filesystems.disks.copy-overlap' => [
        'driver' => 'local',
        'root' => $this->sourceDisk->path('').$suffix,
    ]]);
    $this->sourceDisk->put('keep.txt', 'keep');

    $this->artisan('storage:copy copy-source copy-overlap')
        ->expectsOutput('Source and destination storage locations must not overlap.')
        ->assertFailed();

    expect($this->sourceDisk->get('keep.txt'))->toBe('keep');
})->with(['same root' => '', 'nested root' => 'nested']);

it('rejects overlapping prefixes in the same S3 bucket without contacting the server', function (string $root) {
    $config = [
        'driver' => 's3',
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'auto',
        'bucket' => 'test-bucket',
        'endpoint' => 'https://storage.invalid',
        'root' => 'content',
    ];
    config([
        'filesystems.disks.copy-s3-source' => $config,
        'filesystems.disks.copy-s3-destination' => [...$config, 'root' => $root],
    ]);

    $this->artisan('storage:copy copy-s3-source copy-s3-destination')
        ->expectsOutput('Source and destination storage locations must not overlap.')
        ->assertFailed();
})->with(['same prefix' => 'content', 'child prefix' => 'content/nested', 'parent prefix' => '']);

it('fails on unavailable source streams without writing destination files', function () {
    $this->sourceDisk->put('video.mp4', 'video');
    $source = Mockery::mock(FilesystemAdapter::class, [
        $this->sourceDisk->getDriver(),
        $this->sourceDisk->getAdapter(),
        $this->sourceDisk->getConfig(),
    ])->makePartial();
    $source->shouldReceive('readStream')->once()->andReturnNull();
    Storage::set('copy-source', $source);

    $this->artisan('storage:copy copy-source copy-destination')
        ->expectsOutput('Copied: 0; skipped: 0; failed: 1.')
        ->assertFailed();

    $this->destinationDisk->assertMissing('video.mp4');
    expect($this->sourceDisk->get('video.mp4'))->toBe('video');
});

it('continues after write failures and closes source streams', function (bool $throw) {
    $this->sourceDisk->put('failed.mp4', 'failed');
    $this->sourceDisk->put('success.mp4', 'success');
    $destination = Mockery::mock(FilesystemAdapter::class, [
        $this->destinationDisk->getDriver(),
        $this->destinationDisk->getAdapter(),
        $this->destinationDisk->getConfig(),
    ])->makePartial();
    $streams = [];
    $destination->shouldReceive('writeStream')->andReturnUsing(function ($path, $stream, $options) use (&$streams, $throw) {
        $streams[] = $stream;

        expect(is_resource($stream))->toBeTrue()
            ->and($options['mimetype'])->toBe($this->sourceDisk->mimeType($path));

        if ($path === 'failed.mp4') {
            if ($throw) {
                throw new RuntimeException('Write failed.');
            }

            return false;
        }

        return $this->destinationDisk->writeStream($path, $stream, $options);
    });
    Storage::set('copy-destination', $destination);

    $this->artisan('storage:copy copy-source copy-destination')
        ->expectsOutput('Copied: 1; skipped: 0; failed: 1.')
        ->assertFailed();

    expect($this->destinationDisk->get('success.mp4'))->toBe('success')
        ->and($this->sourceDisk->get('failed.mp4'))->toBe('failed');

    foreach ($streams as $stream) {
        expect(is_resource($stream))->toBeFalse();
    }
})->with(['returned failure' => false, 'thrown failure' => true]);

it('detects writes with incorrect sizes and can repair them on a subsequent run', function (string $corrupted) {
    $this->sourceDisk->put('video.mp4', 'original');
    $destination = Mockery::mock(FilesystemAdapter::class, [
        $this->destinationDisk->getDriver(),
        $this->destinationDisk->getAdapter(),
        $this->destinationDisk->getConfig(),
    ])->makePartial();
    $destination->shouldReceive('writeStream')->once()->andReturnUsing(function ($path) use ($corrupted) {
        return $this->destinationDisk->put($path, $corrupted);
    });
    Storage::set('copy-destination', $destination);

    $this->artisan('storage:copy copy-source copy-destination')
        ->expectsOutputToContain('Destination size does not match the source.')
        ->assertFailed();

    expect($this->sourceDisk->get('video.mp4'))->toBe('original');
    Storage::set('copy-destination', $this->destinationDisk);

    $this->artisan('storage:copy copy-source copy-destination')->assertSuccessful();

    expect($this->destinationDisk->get('video.mp4'))->toBe('original');
})->with(['truncated' => 'short', 'larger file' => 'longer contents']);

it('returns failure if the source listing cannot finish', function () {
    $adapter = Mockery::mock(FlysystemAdapter::class);
    $adapter->shouldReceive('listContents')->with('', true)
        ->andThrow(UnableToListContents::atLocation('', true, new RuntimeException('Listing failed')));
    $source = new FilesystemAdapter(new Filesystem($adapter), $adapter, $this->sourceDisk->getConfig());
    Storage::set('copy-source', $source);

    $this->artisan('storage:copy copy-source copy-destination')
        ->expectsOutputToContain('Unable to finish listing [copy-source]')
        ->assertFailed();

    $this->destinationDisk->assertDirectoryEmpty('/');
});
