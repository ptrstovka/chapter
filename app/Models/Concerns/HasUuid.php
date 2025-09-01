<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string $uuid
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasUuid
{
    public function initializeHasUuid(): void
    {
        static::creating(function (Model $model) {
            if ($model->uuid == null) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /**
     * Find model by UUID.
     */
    public static function findByUuid(string $uuid): ?static
    {
        return static::query()->firstWhere('uuid', $uuid);
    }

    /**
     * Get the model by UUID or throw exception where it does not exist.
     */
    public static function findOrFailByUuid(string $uuid): static
    {
        return static::query()->where('uuid', $uuid)->firstOrFail();
    }
}
