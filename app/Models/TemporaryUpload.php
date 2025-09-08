<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Dimensions;

/**
 * @property string $disk
 * @property string $path
 * @property string $scope
 * @property string $mime_type
 * @property string $client_file_name
 * @property int $size
 * @property \App\Models\User|null $user
 */
class TemporaryUpload extends Model
{
    use HasUuid, Prunable, SoftDeletes;

    protected $guarded = false;

    protected static function booted(): void
    {
        static::forceDeleted(function (TemporaryUpload $upload) {
            if (Storage::disk($upload->disk)->exists($upload->path)) {
                Storage::disk($upload->disk)->delete($upload->path);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<=', now()->subHours(24));
    }

    /**
     * Get the file URL.
     */
    public function url(): ?string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get the disk where to store the temporary file.
     */
    public static function disk(string $scope): string
    {
        return 'public';
    }

    /**
     * Get the directory where to store the temporary file.
     */
    public static function dir(string $scope): string
    {
        return 'tmp';
    }

    /**
     * Copy file to a disk, returning full path of the file.
     */
    public function copyTo(string $disk, ?string $directory = null, ?string $name = null): string
    {
        $name = $name ?: Str::random(20);

        $path = ($directory ? $directory.DIRECTORY_SEPARATOR : '').$name.'.'.File::extension($this->path);

        Storage::disk($disk)->writeStream(path: $path, resource: Storage::disk($this->disk)->readStream($this->path));

        return $path;
    }

    /**
     * Get the human-readable file size.
     */
    public function getHumanReadableSize(): string
    {
        return Number::fileSize($this->size);
    }

    /**
     * List of available temporary file scopes.
     */
    public static function scopes(): array
    {
        return [
            'CourseCoverImage' => [
                'image',
                'max:8192',
                'dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000',
                'extensions:jpg,png,jpeg',
                'mimes:jpg,png,jpeg',
            ],
            'CourseTrailerVideo' => [
                // TODO: FFProbe?
                Rule::file()
                    ->max(256 * 1024) // 256 MB
                    ->extensions(['mp4']),
            ],
            'CourseVideo' => [
                // TODO: FFProbe?
                Rule::file()
                    ->max(2 * 1024 * 1024) // 2 GB
                    ->extensions(['mp4']),
            ],
            'CourseResource' => [
                Rule::file()
                    ->max(2 * 1024 * 1024), // 2 GB
            ],
            'AppLogo' => [
                Rule::imageFile()
                    ->extensions(['jpg', 'png'])
                    ->max(2048)
                    ->dimensions(
                        (new Dimensions)
                            ->minWidth(64)
                            ->minHeight(64)
                            ->maxWidth(1024)
                            ->maxHeight(1024)
                            ->ratio(1)
                    ),
            ],
            'InstructorAvatar' => [
                Rule::imageFile()
                    ->extensions(['jpg', 'png'])
                    ->max(2048)
                    ->dimensions(
                        (new Dimensions)
                            ->minWidth(128)
                            ->minHeight(128)
                            ->maxWidth(2048)
                            ->maxHeight(2048)
                    ),
            ],
        ];
    }
}
