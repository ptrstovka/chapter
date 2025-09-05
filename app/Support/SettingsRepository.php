<?php

namespace App\Support;

use App\Enums\Preference as PreferenceKey;
use App\Models\Preference;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SettingsRepository
{
    /**
     * Memory cache for quick access of already accessed preferences.
     */
    protected static array $memory = [];

    /**
     * List of missing preferences.
     */
    protected static array $missing = [];

    public function __construct(
        protected ?Model $configurable = null
    ) {}

    /**
     * Retrieve value of preference.
     */
    public function get(string|PreferenceKey $key, mixed $default = null): mixed
    {
        if ($key instanceof PreferenceKey) {
            if (func_num_args() === 1) {
                $default = $key->defaultValue();
            }

            $key = $key->value;
        }

        $value = $this->getValue($key, $default);

        // Check if the value is JSON. If so, we parse it as array.
        if (is_string($value) && Str::startsWith($value, ['{', '['])) {
            if ($json = json_decode($value, true)) {
                return $json;
            }
        }

        return $value;
    }

    /**
     * Retrieve boolean preference.
     */
    public function boolean(string|PreferenceKey $key, bool $default = false): bool
    {
        if ($key instanceof PreferenceKey) {
            if (func_num_args() === 1) {
                $defaultValue = $key->defaultValue();

                if (is_bool($defaultValue)) {
                    $default = $defaultValue;
                }
            }

            $key = $key->value;
        }

        $value = $this->getValue($key, $default);

        return $value === '1' || $value === 1 || $value === true || (is_string($value) && in_array(Str::lower($value), ['yes', 'on', 'true', 'enabled']));
    }

    /**
     * Retrieve string preference.
     */
    public function string(string|PreferenceKey $key, ?string $default = null): ?string
    {
        if ($key instanceof PreferenceKey) {
            if (func_num_args() === 1) {
                $default = $key->defaultValue();
            }

            $key = $key->value;
        }

        $value = $this->getValue($key, $default);

        if ($value === $default) {
            return $default;
        }

        if (is_string($value) && Str::length(trim($value)) > 0) {
            return $value;
        }

        return $default;
    }

    /**
     * Retrieve preference value as array.
     */
    public function array(string|PreferenceKey $key, ?array $default = null): ?array
    {
        if ($key instanceof PreferenceKey) {
            if (func_num_args() === 1) {
                $default = $key->defaultValue();
            }

            $key = $key->value;
        }

        $value = $this->getValue($key);

        if (is_string($value)) {
            if ($json = json_decode($value, true)) {
                return $json;
            }
        }

        return $default;
    }

    /**
     * Retrieve preference value as JSON. Alias to array.
     */
    public function json(string|PreferenceKey $key, ?array $default = null): ?array
    {
        return $this->array($key, $default);
    }

    /**
     * Retrieve preference value as integer.
     */
    public function integer(string|PreferenceKey $key, ?int $default = null): ?int
    {
        if ($key instanceof PreferenceKey) {
            if (func_num_args() === 1) {
                $default = $key->defaultValue();
            }

            $key = $key->value;
        }

        $value = $this->getValue($key);

        if (is_numeric($value)) {
            return $value;
        }

        return $default;
    }

    /**
     * Retrieve preference value as float.
     */
    public function float(string|PreferenceKey $key, ?float $default = null): ?float
    {
        if ($key instanceof PreferenceKey) {
            if (func_num_args() === 1) {
                $default = $key->defaultValue();
            }

            $key = $key->value;
        }

        $value = $this->getValue($key);

        if (is_numeric($value)) {
            return $value;
        }

        return $default;
    }

    /**
     * Retrieve preference value as enum.
     */
    public function enum(string|PreferenceKey $key, string $enumClass, $default = null): mixed
    {
        if ($key instanceof PreferenceKey) {
            if (func_num_args() === 1) {
                $default = $key->defaultValue();
            }

            $key = $key->value;
        }

        $value = $this->getValue($key);

        if ($value) {
            return $enumClass::tryFrom($value) ?: $default;
        }

        return $default;
    }

    /**
     * Retrieve preference value as collection.
     */
    public function collect(string|PreferenceKey $key): Collection
    {
        $value = $this->array($key);

        if (is_array($value)) {
            return collect($value);
        }

        return collect();
    }

    /**
     * Retrieve setting by given key.
     */
    protected function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->resolveCacheKey($key);

        // If the preference is not defined, we return default value immediately.
        if (Arr::has(static::$missing, $cacheKey)) {
            return $default;
        }

        // Lookup in memory cache.
        if (Arr::has(static::$memory, $cacheKey)) {
            return Arr::get(static::$memory, $cacheKey);
        }

        // Lookup in application cache.
        if (Cache::has($cacheKey)) {
            $value = Cache::get($cacheKey);
            Arr::set(static::$memory, $cacheKey, $value);

            return $value;
        }

        // Lookup in database.
        $preference = Preference::query()
            ->when($this->configurable, fn (Builder $query) => $query->whereMorphedTo('configurable', $this->configurable))
            ->firstWhere('preference_key', $key);

        if ($preference instanceof Preference) {
            $value = $preference->preference_value;

            Cache::put($cacheKey, $value);
            Arr::set(static::$memory, $cacheKey, $value);

            return $value;
        }

        // Remember that preference does not exist.
        Arr::set(static::$missing, $cacheKey, true);

        return $default;
    }

    /**
     * Set preference value or list of preferences.
     */
    public function set(string|PreferenceKey|array $key, mixed $value = null): void
    {
        if ($key instanceof PreferenceKey) {
            $key = $key->value;
        }

        if (is_array($key) && $value == null) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }

            return;
        }

        $value = $value instanceof BackedEnum
            ? $value->value
            : $value;

        if (is_array($value)) {
            $value = json_encode($value);
        }

        $preference = Preference::query()
            ->when($this->configurable, fn (Builder $query) => $query->whereMorphedTo('configurable', $this->configurable))
            ->firstWhere('preference_key', $key);

        if ($preference instanceof Preference) {
            $preference->update([
                'preference_value' => $value,
            ]);
        } else {
            $preference = new Preference([
                'preference_key' => $key,
                'preference_value' => $value,
            ]);
            $preference->configurable()->associate($this->configurable);
            $preference->save();
        }

        $cacheKey = $this->resolveCacheKey($key);
        Cache::set($cacheKey, $preference->preference_value);
        static::$memory[$cacheKey] = $preference->preference_value;
        if (Arr::has(static::$missing, $cacheKey)) {
            Arr::forget(static::$missing, $cacheKey);
        }
    }

    /**
     * Delete preferences by keys.
     */
    public function forget(string|PreferenceKey|array $key): void
    {
        if ($key instanceof PreferenceKey) {
            $key = $key->value;
        }

        if (is_string($key) && Str::contains($key, '*')) {
            $this->removePreferences(
                Preference::query()
                    ->when($this->configurable, fn (Builder $query) => $query->whereMorphedTo('configurable', $this->configurable))
                    ->where('preference_key', 'like', Str::replace('*', '%', $key))
                    ->get()
            );

            return;
        }

        $keys = Arr::wrap($key);

        if (empty($keys)) {
            return;
        }

        $keys = array_map(fn (string|PreferenceKey $key) => $key instanceof PreferenceKey ? $key->value : $key, $keys);

        $this->removePreferences(
            Preference::query()
                ->when($this->configurable, fn (Builder $query) => $query->whereMorphedTo('configurable', $this->configurable))
                ->whereIn('preference_key', $keys)
                ->get()
        );
    }

    /**
     * Remove collection of preferences.
     */
    protected function removePreferences(Collection $preferences): void
    {
        if ($preferences->isEmpty()) {
            return;
        }

        $preferences->each(function (Preference $preference) {
            $cacheKey = $this->resolveCacheKey($preference->preference_key);

            $preference->delete();

            Arr::set(static::$missing, $cacheKey, true);
            Cache::forget($cacheKey);
            Arr::forget(static::$memory, $cacheKey);
        });
    }

    /**
     * Create a cache key for given preference.
     */
    protected function resolveCacheKey(string $key): string
    {
        if ($this->configurable) {
            return "preference:{$key}:".get_class($this->configurable).'@'.$this->configurable->getKey();
        }

        return "preference:{$key}";
    }

    /**
     * Create new repository instance scoped to given model.
     */
    public static function for(Model $model): static
    {
        return new static($model);
    }
}
