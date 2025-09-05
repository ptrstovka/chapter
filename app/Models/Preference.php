<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $preference_key
 * @property string|null $preference_value
 * @property \Illuminate\Database\Eloquent\Model|null $configurable
 */
class Preference extends Model
{
    protected $guarded = false;

    public function configurable(): MorphTo
    {
        return $this->morphTo();
    }
}
