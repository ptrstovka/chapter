<?php

namespace App\Http\Controllers;

use App\Enums\Preference;
use App\Facades\Settings;

class HomeController
{
    public function __invoke()
    {
        if (Settings::boolean(Preference::EnableExplorePage)) {
            return to_route('explore');
        }

        return to_route('courses');
    }
}
