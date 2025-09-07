<?php

namespace App\Http\Middleware;

use App\Enums\Preference;
use App\Facades\Settings;
use App\View\Models\UserViewViewModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => fn () => [
                'name' => Settings::get(Preference::PlatformName),
                'locale' => App::getLocale(),
                'enableExplorePage' => Settings::boolean(Preference::EnableExplorePage),
                'primaryColorForeground' => Settings::string(Preference::PrimaryColorForeground),
                'primaryColorBackground' => Settings::string(Preference::PrimaryColorBackground),
                'primaryColorForegroundDark' => Settings::string(Preference::PrimaryColorDarkForeground),
                'primaryColorBackgroundDark' => Settings::string(Preference::PrimaryColorDarkBackground),
            ],
            'auth' => fn () => [
                'user' => UserViewViewModel::from($request->user()),
            ],
        ];
    }
}
