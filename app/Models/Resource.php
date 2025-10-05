<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property string $file_path
 * @property string $client_file_name
 * @property int $size
 * @property string $mime_type
 * @property \App\Models\Course $course
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Lesson> $lessons
 */
class Resource extends Model
{
    use HasUuid;

    protected $guarded = false;

    protected static function booted(): void
    {
        static::deleted(function (Resource $resource) {
            $disk = App::contentDisk();

            if (Storage::disk($disk)->exists($resource->file_path)) {
                Storage::disk($disk)->delete($resource->file_path);
            }
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class);
    }

    /**
     * Get direct URL to the resource.
     */
    public function getDirectUrl(): string
    {
        return Storage::disk(App::contentDisk())->url($this->file_path);
    }

    /**
     * Get the download URL for the resource.
     */
    public function getDownloadUrl(): string
    {
        return route('resources.show', [$this->course, $this]);
    }

    /**
     * Download the file.
     */
    public function download(): StreamedResponse
    {
        return Storage::disk(App::contentDisk())->download($this->file_path, $this->client_file_name);
    }

    /**
     * Get the directory where on the disk are resources stored.
     */
    public static function dir(): string
    {
        return 'course-resources';
    }
}
