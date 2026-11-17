<?php

namespace App\Models;

use App\Enums\AccessGroupVisibility;
use Database\Factories\AccessGroupFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property AccessGroupVisibility $visibility
 * @property Collection<int, User> $users
 * @property int|null $users_count
 *
 * @method static \Database\Factories\AccessGroupFactory factory($count = null, $state = [])
 */
class AccessGroup extends Model
{
    /** @use HasFactory<AccessGroupFactory> */
    use HasFactory;

    protected $guarded = false;

    protected $attributes = [
        'visibility' => 'public',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => AccessGroupVisibility::class,
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'access_group_user')->withTimestamps();
    }
}
