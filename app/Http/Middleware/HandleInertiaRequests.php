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
            'appName' => fn () => Settings::get(Preference::PlatformName),
            'locale' => fn () => App::getLocale(),
            'auth' => fn () => [
                'user' => UserViewViewModel::from($request->user()),
            ],
        ];
    }
}
