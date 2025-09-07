<?php


namespace App\Http\Controllers\Admin;


use App\Enums\Preference;
use App\Facades\Settings;
use App\Support\Patch;
use App\View\Layouts\AdminLayout;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;
use StackTrace\Ui\SelectOption;

class SettingsController
{
    public function index()
    {
        return Inertia::render('Admin/SettingsPage', AdminLayout::make([
            'platformName' => Settings::get(Preference::PlatformName),
            'platformLocale' => Settings::get(Preference::PlatformLocale),
            'availableLocales' => collect(config('app.available_locales'))
                ->map(fn (string $locale) => new SelectOption(__("Locale:{$locale}"), $locale))
                ->sortBy('label')
                ->values(),
            'enableRegistration' => Settings::boolean(Preference::EnableRegistration),
            'enableInvitations' => Settings::boolean(Preference::EnableInvitations),
            'enableSingleSignOn' => Settings::boolean(Preference::EnableSingleSignOn),
            'enablePasswordLogin' => Settings::boolean(Preference::EnablePasswordLogin),
            'enableExplorePage' => Settings::boolean(Preference::EnableExplorePage),
            'primaryColorForeground' => Settings::string(Preference::PrimaryColorForeground),
            'primaryColorBackground' => Settings::string(Preference::PrimaryColorBackground),
            'primaryColorDarkForeground' => Settings::string(Preference::PrimaryColorDarkForeground),
            'primaryColorDarkBackground' => Settings::string(Preference::PrimaryColorDarkBackground),
        ])->breadcrumb(BreadcrumbItem::make(__('Settings'))));
    }

    public function update(Request $request)
    {
        $colorValueRegex = 'regex:/^(?:oklch\(\s*(?:\d+(?:\.\d+)?|\.\d+)\s+(?:\d+(?:\.\d+)?|\.\d+)\s+(?:\d+(?:\.\d+)?|\.\d+)\s*\)|hsla\(\s*[-+]?\d+(?:\.\d+)?\s*,\s*[-+]?\d+(?:\.\d+)?%\s*,\s*[-+]?\d+(?:\.\d+)?%\s*,\s*(?:\d+(?:\.\d+)?|\.\d+)\s*\))$/i';

        $validated = $request->validate([
            'platform_name' => ['sometimes', 'required', 'string', 'max:191'],
            'platform_locale' => ['sometimes', 'required', 'string', Rule::in(config('app.available_locales'))],
            'primary_color_foreground' => ['sometimes', 'nullable', 'string', $colorValueRegex],
            'primary_color_background' => ['sometimes', 'nullable', 'string', $colorValueRegex],
            'primary_color_dark_foreground' => ['sometimes', 'nullable', 'string', $colorValueRegex],
            'primary_color_dark_background' => ['sometimes', 'nullable', 'string', $colorValueRegex],
            'enable_registration' => ['sometimes', 'boolean'],
            'enable_invitations' => ['sometimes', 'boolean'],
            'enable_single_sign_on' => ['sometimes', 'boolean'],
            'enable_password_login' => ['sometimes', 'boolean'],
            'enable_explore_page' => ['sometimes', 'boolean'],
        ]);

        $patch = new Patch($validated);

        $patch->present('platform_name', fn (?string $value) => Settings::setOrForget(Preference::PlatformName, $value));
        $patch->present('platform_locale', fn (string $value) => Settings::setOrForget(Preference::PlatformLocale, $value));
        $patch->present('primary_color_foreground', fn (?string $value) => Settings::setOrForget(Preference::PrimaryColorForeground, $value));
        $patch->present('primary_color_background', fn (?string $value) => Settings::setOrForget(Preference::PrimaryColorBackground, $value));
        $patch->present('primary_color_dark_foreground', fn (?string $value) => Settings::setOrForget(Preference::PrimaryColorDarkForeground, $value));
        $patch->present('primary_color_dark_background', fn (?string $value) => Settings::setOrForget(Preference::PrimaryColorDarkBackground, $value));

        $patch->present('enable_registration', fn (bool $value) => Settings::setOrForget(Preference::EnableRegistration, $value));
        $patch->present('enable_invitations', fn (bool $value) => Settings::setOrForget(Preference::EnableInvitations, $value));
        $patch->present('enable_single_sign_on', fn (bool $value) => Settings::setOrForget(Preference::EnableSingleSignOn, $value));
        $patch->present('enable_password_login', fn (bool $value) => Settings::setOrForget(Preference::EnablePasswordLogin, $value));

        $patch->present('enable_explore_page', fn (bool $value) => Settings::setOrForget(Preference::EnableExplorePage, $value));

        return back();
    }
}
