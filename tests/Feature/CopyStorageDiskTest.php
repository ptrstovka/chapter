<?php

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\UnableToListContents;

beforeEach(function () {
    config([
        'filesystems.disks.copy-source' => ['driver' => 'local'],
        'filesystems.disks.copy-destination' => ['driver' => 'local'],
    ]);

    $this->sourceDisk = Storage::fake('copy-source', ['driver' => 'local']);
    $this->destinationDisk = Storage::fake('copy-destination', ['driver' => 'local']);
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

it('resumes without rewriting verified files', function () {
    $this->sourceDisk->put('video.mp4', 'video');

    $this->artisan('storage:copy copy-source copy-destination')->assertSuccessful();

    $destination = Mockery::mock(FilesystemAdapter::class, [
        $this->destinationDisk->getDriver(),
        $this->destinationDisk->getAdapter(),
        $this->destinationDisk->getConfig(),
    ])->makePartial();
    $destination->shouldNotReceive('writeStream');
    Storage::set('copy-destination', $destination);

    $this->artisan('storage:copy copy-source copy-destination')
        ->expectsOutput('Copied: 0; skipped: 1; failed: 0.')
        ->assertSuccessful();
});

it('replaces incomplete or different destination files even when their sizes match', function (string $existing) {
    $this->sourceDisk->put('video.mp4', 'original');
    $this->destinationDisk->put('video.mp4', $existing);

    $this->artisan('storage:copy copy-source copy-destination')
        ->expectsOutput('Copied: 1; skipped: 0; failed: 0.')
        ->assertSuccessful();

    expect($this->destinationDisk->get('video.mp4'))->toBe('original')
        ->and($this->sourceDisk->get('video.mp4'))->toBe('original');
})->with(['partial copy' => 'part', 'same size different bytes' => 'modified']);

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

it('fails on unavailable source streams or checksums without writing destination files', function (string $method, mixed $result) {
    $this->sourceDisk->put('video.mp4', 'video');
    $source = Mockery::mock(FilesystemAdapter::class, [
        $this->sourceDisk->getDriver(),
        $this->sourceDisk->getAdapter(),
        $this->sourceDisk->getConfig(),
    ])->makePartial();
    $source->shouldReceive($method)->once()->andReturn($result);
    Storage::set('copy-source', $source);

    $this->artisan('storage:copy copy-source copy-destination')
        ->expectsOutput('Copied: 0; skipped: 0; failed: 1.')
        ->assertFailed();

    $this->destinationDisk->assertMissing('video.mp4');
    expect($this->sourceDisk->get('video.mp4'))->toBe('video');
})->with([
    'read failure' => ['readStream', null],
    'checksum failure' => ['checksum', false],
]);

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

it('detects corrupted writes and can repair them on a subsequent run', function (string $corrupted) {
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
        ->expectsOutputToContain('Destination size or SHA-256 checksum does not match the source.')
        ->assertFailed();

    expect($this->sourceDisk->get('video.mp4'))->toBe('original');
    Storage::set('copy-destination', $this->destinationDisk);

    $this->artisan('storage:copy copy-source copy-destination')->assertSuccessful();

    expect($this->destinationDisk->get('video.mp4'))->toBe('original');
})->with(['truncated' => 'short', 'same size corrupted' => 'modified']);

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
