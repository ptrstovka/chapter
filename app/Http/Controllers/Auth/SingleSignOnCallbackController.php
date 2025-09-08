<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Preference;
use App\Facades\Settings;
use App\Models\SingleSignOnProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use StackTrace\Ui\Facades\Toast;
use Throwable;

class SingleSignOnCallbackController
{
    public function __invoke(Request $request, SingleSignOnProvider $provider)
    {
        abort_unless(Settings::boolean(Preference::EnableSingleSignOn), 404);
        abort_unless($provider->is_active, 404);

        try {
            $externalUser = $provider->createDriver()->user();

            if ($email = $externalUser->getEmail()) {
                $user = User::query()->firstWhere('email', $email) ?: User::create([
                    'email' => $email,
                    'name' => $externalUser->getName() ?: $email,
                    'password' => Hash::make(Str::random(64)),
                    'is_admin' => false,
                ]);

                Auth::login($user, remember: true);

                $request->session()->regenerate();

                return redirect()->intended(route('home', absolute: false));
            } else {
                throw new InvalidArgumentException('The provider does not returned user email');
            }
        } catch (Throwable $e) {
            if (App::isLocal()) {
                throw $e;
            }

            report($e);

            Toast::destructive(__('Oops, something went wrong…'));

            return to_route('home');
        }
    }
}
