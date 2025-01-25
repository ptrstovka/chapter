<?php


namespace App\Models\Concerns;


use Illuminate\Support\Arr;

/**
 * @property string $slug_id
 */
trait HasSlugId
{
    use HasHashId {
        HasHashId::resolveRouteBindingQuery as resolveHashIdRouteBindingQuery;
    }

    public function getSlugIdAttribute(): string
    {
        return $this->slug.'-'.$this->hid;
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($field && Arr::last(explode('.', $field)) === 'slugid' && is_string($value) && ($hid = Arr::last(explode('-', $value)))) {
            return $query->whereHid($hid);
        }

        return $this->resolveHashIdRouteBindingQuery($query, $value, $field);
    }
}
