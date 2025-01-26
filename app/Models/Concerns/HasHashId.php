<?php

namespace App\Models\Concerns;

use Hashids\Hashids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 *
 * @property string $hid
 */
trait HasHashId
{
    /**
     * Already generated hash ID for the model.
     */
    private ?string $cachedHashId = null;

    /**
     * Add clause to query model based on its hash id.
     */
    public function scopeWhereHid(Builder $builder, $id): void
    {
        $hasher = $this->makeHashids();

        $id = Arr::first($hasher->decode($id));

        $builder->where($this->qualifyColumn($this->getKeyName()), $id);
    }

    /**
     *  Accessor for the hash id of the model.
     */
    public function getHidAttribute(): string
    {
        if ($this->cachedHashId != null) {
            return $this->cachedHashId;
        }

        $hashids = $this->makeHashids();

        return $this->cachedHashId = $hashids->encode($this->getKey());
    }

    /**
     * Creates new Hashids instance.
     */
    protected function makeHashids(): Hashids
    {
        return new Hashids($this->getHashIdSalt(), $this->getMinHashIdLength(), $this->getHashIdAlphabet());
    }

    /**
     * The alphabet used for hash ID generation.
     */
    protected function getHashIdAlphabet(): string
    {
        return 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    }

    /**
     * The salt string used for hash ID generation.
     */
    protected function getHashIdSalt(): string
    {
        return hash('sha256', get_called_class().':'.Str::of(env('APP_KEY'))->reverse()->substr(2, 12)->value());
    }

    /**
     * The minimum length of the hash ID.
     */
    protected function getMinHashIdLength(): int
    {
        return 10;
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($field && Arr::last(explode('.', $field)) === 'hid') {
            return $query->whereHid($value);
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }
}
