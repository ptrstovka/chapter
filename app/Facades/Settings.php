<?php


namespace App\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(string|\App\Enums\Preference $key, mixed $default = null)
 * @method static boolean boolean(string|\App\Enums\Preference $key, boolean $default = false)
 * @method static string|null string(string|\App\Enums\Preference $key, ?string $default = null)
 * @method static array|null array(string|\App\Enums\Preference $key, ?array $default = null)
 * @method static array|null json(string|\App\Enums\Preference $key, ?array $default = null)
 * @method static int|null integer(string|\App\Enums\Preference $key, ?int $default = null)
 * @method static float|null float(string|\App\Enums\Preference $key, ?float $default = null)
 * @method static mixed enum(string|\App\Enums\Preference $key, string $enumClass, $default = null)
 * @method static void set(string|\App\Enums\Preference|array $key, mixed $value = null)
 * @method static void forget(string|\App\Enums\Preference|array $keys)
 * @method static \Illuminate\Support\Collection collect(string|\App\Enums\Preference $key)
 * @method static \App\Support\SettingsRepository for(\Illuminate\Database\Eloquent\Model $model)
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'settings';
    }
}
