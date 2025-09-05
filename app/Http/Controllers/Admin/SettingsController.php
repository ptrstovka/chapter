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
        ])->breadcrumb(BreadcrumbItem::make(__('Settings'))));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'platform_name' => ['sometimes', 'required', 'string', 'max:191'],
            'platform_locale' => ['sometimes', 'required', 'string', Rule::in(config('app.available_locales'))],
        ]);

        $patch = new Patch($validated);

        $patch->present('platform_name', fn (?string $value) => Settings::set(Preference::PlatformName, $value));
        $patch->present('platform_locale', fn (string $value) => Settings::set(Preference::PlatformLocale, $value));

        return back();
    }
}
