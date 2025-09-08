<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Preference;
use App\Facades\Settings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\SingleSignOnProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        $singleSignOnEnabled = Settings::boolean(Preference::EnableSingleSignOn);
        $passwordLoginEnabled = Settings::boolean(Preference::EnablePasswordLogin);

        $singleSignOnProviders = $singleSignOnEnabled
            ? SingleSignOnProvider::query()
                ->where('is_active', true)
                ->get()
                ->map(fn (SingleSignOnProvider $provider) => [
                    'url' => $provider->getRedirectUrl(),
                    'title' => $provider->getLoginButtonTitle(),
                    'textColor' => $provider->getLoginButtonTextColor(),
                    'backgroundColor' => $provider->getLoginButtonBackgroundColor(),
                ])
            : collect();

        return Inertia::render('Auth/Login', [
            'status' => session('status'),

            'canResetPassword' => $passwordLoginEnabled,
            'canRegister' => Settings::boolean(Preference::EnableRegistration),

            'passwordLoginEnabled' => $passwordLoginEnabled,
            'singleSignOnEnabled' => $singleSignOnEnabled,
            'singleSignOnProviders' => $singleSignOnProviders,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        abort_unless(Settings::boolean(Preference::EnablePasswordLogin), 404);

        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
