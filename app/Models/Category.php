<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 *
 * @method static \Database\Factories\CategoryFactory factory($count = null, $state = [])
 */
class Category extends Model
{
    use HasFactory;

    protected $guarded = false;
}
