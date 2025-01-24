<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $name
 * @property string|null $bio
 * @property string|null $avatar_file_path
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
            return Storage::disk('public')->url($this->avatar_file_path);
        }

        return null;
    }
}
