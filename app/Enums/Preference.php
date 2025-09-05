<?php


namespace App\Enums;


enum Preference: string
{
    case PlatformName = 'Platform.Name';
    case PlatformLocale = 'Platform.Locale';

    /**
     * Get the default value of the preference.
     */
    public function defaultValue(): mixed
    {
        return match ($this) {
            Preference::PlatformName => config('app.name'),
            Preference::PlatformLocale => config('app.locale'),
            default => null,
        };
    }
}
