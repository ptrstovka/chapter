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
}
