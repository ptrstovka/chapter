<?php

namespace App\Support;

use Illuminate\Support\Str;

class Slug
{
    /**
     * Generate a unique slug from given value for a model column.
     */
    public static function unique(string $value, string $model, string $column): string
    {
        $slug = Str::slug($value);
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $model;

        while ($model->newQuery()->where($column, $slug)->exists()) {
            $slug = Str::slug($value).'-'.Str::lower(Str::random(2));
        }

        return $slug;
    }
}
