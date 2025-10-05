<?php

namespace App\Models;

use App\Enums\TextContentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $name
 * @property string|null $bio
 * @property string|null $avatar_file_path
 *
 * @method static \Database\Factories\AuthorFactory factory($count = null, $state = [])
 *
 * TODO: Rename to Instructor
 */
class Author extends Model
{
    use HasFactory;

    protected $guarded = false;

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Retrieve URL to author avatar.
     */
    public function getAvatarUrl(): ?string
    {
        if ($this->avatar_file_path) {
            return Storage::disk(App::contentDisk())->url($this->avatar_file_path);
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
