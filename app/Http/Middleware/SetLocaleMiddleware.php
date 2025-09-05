<?php

namespace App\Http\Middleware;

use App\Enums\Preference;
use App\Facades\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (($locale = Settings::get(Preference::PlatformLocale)) && in_array($locale, config('app.available_locales'))) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
