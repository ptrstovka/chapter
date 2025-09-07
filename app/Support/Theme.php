<?php

namespace App\Support;

use App\Enums\Preference;
use App\Facades\Settings;
use Illuminate\Support\Facades\Request;

final readonly class Theme
{
    /**
     * The default primary background color for light appearance.
     */
    const string DEFAULT_LIGHT_PRIMARY_BACKGROUND_COLOR = 'oklch(0.606 0.25 292.717)';

    /**
     * The default primary foreground color for light appearance.
     */
    const string DEFAULT_LIGHT_PRIMARY_FOREGROUND_COLOR = 'oklch(0.969 0.016 293.756)';

    /**
     * The default primary background color for dark appearance.
     */
    const string DEFAULT_DARK_PRIMARY_BACKGROUND_COLOR = 'oklch(0.541 0.281 293.009)';

    /**
     * The default primary foreground color for dark appearance.
     */
    const string DEFAULT_DARK_PRIMARY_FOREGROUND_COLOR = 'oklch(0.969 0.016 293.756)';

    /**
     * Get the appearance setting.
     */
    public static function appearance(): string
    {
        if (($appearance = Request::cookie('appearance')) && in_array($appearance, ['dark', 'light', 'system'])) {
            return $appearance;
        }

        return 'system';
    }

    /**
     * Get the primary background color for light appearance.
     */
    public static function primaryLightBackgroundColor(): string
    {
        return Settings::get(Preference::PrimaryColorBackground) ?: self::DEFAULT_LIGHT_PRIMARY_BACKGROUND_COLOR;
    }

    /**
     * Get the primary foreground color for light appearance.
     */
    public static function primaryLightForegroundColor(): string
    {
        return Settings::get(Preference::PrimaryColorForeground) ?: self::DEFAULT_LIGHT_PRIMARY_FOREGROUND_COLOR;
    }

    /**
     * Get the primary background color for dark appearance.
     */
    public static function primaryDarkBackgroundColor(): string
    {
        return Settings::get(Preference::PrimaryColorDarkBackground) ?: self::DEFAULT_DARK_PRIMARY_BACKGROUND_COLOR;
    }

    /**
     * Get the primary foreground color for dark appearance.
     */
    public static function primaryDarkForegroundColor(): string
    {
        return Settings::get(Preference::PrimaryColorDarkForeground) ?: self::DEFAULT_DARK_PRIMARY_FOREGROUND_COLOR;
    }
}
