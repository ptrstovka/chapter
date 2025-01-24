<?php


namespace App\View\Models;


use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;

abstract class Model implements Arrayable, JsonSerializable
{
    /**
     * Define props of the view model.
     */
    public abstract function toView(): array;

    /**
     * Format key to camel case.
     */
    private function formatKey(string $key): string
    {
        return Str::camel($key);
    }

    /**
     * Format model attributes.
     */
    private function resolveValue(mixed $value): mixed
    {
        if (is_array($value)) {
            // Convert associative array
            if (Arr::isAssoc($value)) {
                return collect($value)
                    ->mapWithKeys(fn ($val, $key) => [$this->formatKey($key) => $this->resolveValue($val)])
                    ->all();
            } else {
                // Convert regular array
                return collect($value)->map(fn ($val) => $this->resolveValue($val))->all();
            }
        } else if ($value instanceof Collection) {
            // Convert collection
            return $value->map(fn ($val) => $this->resolveValue($val))->all();
        } else if ($value instanceof Model) {
            // Convert another view model
            return $this->resolveValue($value->toView());
        }

        return $value;
    }

    public function toArray()
    {
        return $this->resolveValue($this->toView());
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
