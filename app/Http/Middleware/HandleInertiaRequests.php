<?php

namespace App\Http\Middleware;

use App\Enums\Preference;
use App\Facades\Settings;
use App\Support\Theme;
use App\View\Models\UserViewViewModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Number;
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
                'logo' => Theme::logo(),
                'videoUploadMaxSize' => config('video.max_upload_size_mb') * 1024 ** 2,
                'videoUploadMaxSizeLabel' => Number::fileSize(config('video.max_upload_size_mb') * 1024 ** 2),
            ],
            'auth' => fn () => [
                'user' => UserViewViewModel::from($request->user()),
            ],
        ];
    }
}
