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
        ])->breadcrumb(BreadcrumbItem::make(__('Settings'))));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'platform_name' => ['sometimes', 'required', 'string', 'max:191'],
            'platform_locale' => ['sometimes', 'required', 'string', Rule::in(config('app.available_locales'))],
            'enable_registration' => ['sometimes', 'boolean'],
            'enable_invitations' => ['sometimes', 'boolean'],
            'enable_single_sign_on' => ['sometimes', 'boolean'],
            'enable_password_login' => ['sometimes', 'boolean'],
        ]);

        $patch = new Patch($validated);

        $patch->present('platform_name', fn (?string $value) => Settings::set(Preference::PlatformName, $value));
        $patch->present('platform_locale', fn (string $value) => Settings::set(Preference::PlatformLocale, $value));

        $patch->present('enable_registration', fn (bool $value) => Settings::set(Preference::EnableRegistration, $value));
        $patch->present('enable_invitations', fn (bool $value) => Settings::set(Preference::EnableInvitations, $value));
        $patch->present('enable_single_sign_on', fn (bool $value) => Settings::set(Preference::EnableSingleSignOn, $value));
        $patch->present('enable_password_login', fn (bool $value) => Settings::set(Preference::EnablePasswordLogin, $value));

        return back();
    }
}
