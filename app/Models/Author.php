<?php

namespace App\Models;

use App\Enums\TextContentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $name
 * @property string|null $bio
 * @property string|null $avatar_file_path
 *
 * @method static \Database\Factories\AuthorFactory factory($count = null, $state = [])
 */
class Author extends Model
{
    use HasFactory;

    protected $guarded = false;

    /**
     * Retrieve URL to author avatar.
     */
    public function getAvatarUrl(): ?string
    {
        if ($this->avatar_file_path) {
            return Storage::disk(config('filesystems.content_disk'))->url($this->avatar_file_path);
        }

        return null;
    }

    /**
     * Get the default text content type for the user.
     */
    public function getDefaultTextContentType(): TextContentType
    {
        return TextContentType::Html;
    }
}
