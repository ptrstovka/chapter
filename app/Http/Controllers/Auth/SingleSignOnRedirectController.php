<?php


namespace App\Http\Controllers\Auth;


use App\Enums\Preference;
use App\Facades\Settings;
use App\Models\SingleSignOnProvider;
use Illuminate\Support\Facades\App;
use StackTrace\Ui\Facades\Toast;

class SingleSignOnRedirectController
{
    public function __invoke(SingleSignOnProvider $provider)
    {
        abort_unless(Settings::boolean(Preference::EnableSingleSignOn), 404);
        abort_unless($provider->is_active, 404);

        try {
            return $provider->createDriver()->redirect();
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
