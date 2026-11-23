<?php

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\DirectoryListing;
use League\Flysystem\Filesystem;

beforeEach(function () {
    config([
        'filesystems.content_disk' => 'verify-content',
        'filesystems.disks.verify-content' => ['driver' => 'local'],
        'filesystems.disks.verify-other' => ['driver' => 'local'],
    ]);

    $this->contentDisk = Storage::fake('verify-content', ['driver' => 'local']);
    $this->otherDisk = Storage::fake('verify-other', ['driver' => 'local']);
    $this->contentDisk->put('course-videos/existing.mp4', 'Existing video');
    $this->contentDisk->put('.storage-verification/old/probe.txt', 'Existing probe');

    $this->filesystem = Mockery::mock(Filesystem::class, [$this->contentDisk->getAdapter()])->makePartial();
    Storage::set('verify-content', new FilesystemAdapter(
        $this->filesystem,
        $this->contentDisk->getAdapter(),
        $this->contentDisk->getConfig(),
    ));
});

it('verifies the configured content disk and removes only its own probe', function () {
    $files = $this->contentDisk->allFiles();

    $this->artisan('storage:verify')
        ->expectsOutput('Write: OK')
        ->expectsOutput('Metadata: OK')
        ->expectsOutput('List: OK')
        ->expectsOutput('Read: OK')
        ->expectsOutput('Delete: OK')
        ->expectsOutput('Disk [verify-content] verified successfully. Probe file removed.')
        ->assertSuccessful();

    expect($this->contentDisk->allFiles())->toBe($files)
        ->and($this->contentDisk->get('course-videos/existing.mp4'))->toBe('Existing video')
        ->and($this->contentDisk->get('.storage-verification/old/probe.txt'))->toBe('Existing probe');
    $this->otherDisk->assertDirectoryEmpty('/');
});

it('verifies an explicit disk without changing CONTENT_DISK or touching the default disk', function () {
    $this->filesystem->shouldNotReceive('write');
    $this->filesystem->shouldNotReceive('fileExists');

    $this->artisan('storage:verify verify-other')
        ->expectsOutput('Disk [verify-other] verified successfully. Probe file removed.')
        ->assertSuccessful();

    expect(config('filesystems.content_disk'))->toBe('verify-content');
    $this->otherDisk->assertDirectoryEmpty('/');
});

it('rejects an unknown disk before touching storage', function () {
    $this->filesystem->shouldNotReceive('write');

    $this->artisan('storage:verify missing')
        ->expectsOutput('Disk [missing] is not configured.')
        ->assertFailed();
});

it('rejects a missing content disk configuration', function () {
    config(['filesystems.content_disk' => null]);

    $this->artisan('storage:verify')
        ->expectsOutput('CONTENT_DISK is not configured.')
        ->assertFailed();
});

it('reports storage operation failures and still cleans up the probe', function (string $method, string $operation) {
    $files = $this->contentDisk->allFiles();
    $this->filesystem->shouldReceive($method)->once()->andThrow(new RuntimeException('Permission denied.'));

    $this->artisan('storage:verify')
        ->expectsOutput("Disk [verify-content] failed while {$operation}: Permission denied.")
        ->expectsOutput('Delete: OK')
        ->assertFailed();

    expect($this->contentDisk->allFiles())->toBe($files);
})->with([
    'write denied' => ['write', 'writing'],
    'metadata denied' => ['fileSize', 'checking metadata'],
    'list denied' => ['listContents', 'listing'],
    'read denied' => ['readStream', 'reading'],
]);

it('cleans up a write that succeeded remotely but reported an error', function () {
    $files = $this->contentDisk->allFiles();
    $this->filesystem->shouldReceive('write')->once()->andReturnUsing(function ($path, $contents, $options) {
        $this->contentDisk->getDriver()->write($path, $contents, $options);

        throw new RuntimeException('Connection lost after writing.');
    });

    $this->artisan('storage:verify')
        ->expectsOutputToContain('Connection lost after writing.')
        ->expectsOutput('Delete: OK')
        ->assertFailed();

    expect($this->contentDisk->allFiles())->toBe($files);
});

it('rejects an incorrect size or a probe missing from the listing', function (string $method, mixed $result, string $message) {
    $files = $this->contentDisk->allFiles();
    $this->filesystem->shouldReceive($method)->once()->andReturn($result);

    $this->artisan('storage:verify')
        ->expectsOutputToContain($message)
        ->expectsOutput('Delete: OK')
        ->assertFailed();

    expect($this->contentDisk->allFiles())->toBe($files);
})->with([
    'incorrect size' => ['fileSize', 0, 'The probe file is missing or its size does not match.'],
    'missing from list' => ['listContents', new DirectoryListing([]), 'The probe file was not returned in the listing.'],
]);

it('detects incorrect read contents and closes the stream before cleanup', function () {
    $files = $this->contentDisk->allFiles();
    $stream = fopen('php://temp', 'w+');
    fwrite($stream, 'Incorrect contents');
    rewind($stream);
    $this->filesystem->shouldReceive('readStream')->once()->andReturn($stream);

    $this->artisan('storage:verify')
        ->expectsOutputToContain('The probe contents do not match what was written.')
        ->expectsOutput('Delete: OK')
        ->assertFailed();

    expect(is_resource($stream))->toBeFalse()
        ->and($this->contentDisk->allFiles())->toBe($files);
});

it('reports cleanup failures with the remaining probe path', function (bool $throw) {
    $files = $this->contentDisk->allFiles();
    $this->filesystem->shouldReceive('delete')->once()->andReturnUsing(function () use ($throw) {
        if ($throw) {
            throw new RuntimeException('Delete denied.');
        }
    });

    $this->artisan('storage:verify')
        ->expectsOutputToContain('Disk [verify-content] cleanup failed:')
        ->expectsOutputToContain('Check and remove this probe file if it remains: .storage-verification/')
        ->assertFailed();

    expect(array_diff($this->contentDisk->allFiles(), $files))->toHaveCount(1)
        ->and($this->contentDisk->get('course-videos/existing.mp4'))->toBe('Existing video');
})->with(['delete throws' => true, 'delete silently fails' => false]);

it('preserves the original error when cleanup fails as well', function () {
    $this->filesystem->shouldReceive('readStream')->once()->andThrow(new RuntimeException('Read denied.'));
    $this->filesystem->shouldReceive('delete')->once()->andThrow(new RuntimeException('Delete denied.'));

    $this->artisan('storage:verify')
        ->expectsOutput('Disk [verify-content] failed while reading: Read denied.')
        ->expectsOutput('Disk [verify-content] cleanup failed: Delete denied.')
        ->assertFailed();
});

it('does not overwrite or delete a probe path that already exists', function () {
    $files = $this->contentDisk->allFiles();
    $this->filesystem->shouldReceive('fileExists')->once()->andReturn(true);
    $this->filesystem->shouldNotReceive('write');
    $this->filesystem->shouldNotReceive('delete');

    $this->artisan('storage:verify')
        ->expectsOutputToContain('The probe path already exists.')
        ->assertFailed();

    expect($this->contentDisk->allFiles())->toBe($files);
});
