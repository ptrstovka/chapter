<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string|null $bio
 * @property string|null $avatar_file_path
 */
class Author extends Model
{
    use HasFactory;

    protected $guarded = false;
}
