<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Patch
{
    public function __construct(
        protected array $input = []
    ) {}

    /**
     * Update field only if it is present in the input.
     */
    public function present(string $name, Closure $closure): static
    {
        if (Arr::has($this->input, $name)) {
            call_user_func($closure, Arr::get($this->input, $name));
        }

        return $this;
    }

    /**
     * Update all attributes present in the input.
     */
    public function all(Model $model, array $map = []): static
    {
        $attributes = collect($this->input)
            ->mapWithKeys(fn ($value, $key) => [Arr::has($map, $key) ? Arr::get($map, $key) : $key => $value])
            ->all();

        if (! empty($attributes)) {
            $model->update($attributes);
        }

        return $this;
    }

    /**
     * Update only selected fields present in the input.
     */
    public function only(array $fields, Model $model): static
    {
        $attributes = collect($fields)
            ->mapWithKeys(fn ($value, $key) => [is_numeric($key) && is_string($value) ? $value : $key => $value])
            ->flip()
            ->filter(fn (string $field) => Arr::has($this->input, $field))
            ->map(fn (string $field) => Arr::get($this->input, $field))
            ->all();

        if (! empty($attributes)) {
            $model->update($attributes);
        }

        return $this;
    }
}
