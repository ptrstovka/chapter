<?php


namespace App\Http\Controllers\Admin;


use App\Models\SingleSignOnProvider;
use Illuminate\Http\Request;
use StackTrace\Ui\Facades\Toast;

class ToggleSSOProviderController
{
    public function __invoke(Request $request, SingleSignOnProvider $provider)
    {
        $request->validate([
            'is_active' => ['boolean'],
        ]);

        $provider->is_active = $request->boolean('is_active');
        $provider->save();

        if ($provider->is_active) {
            Toast::make(__(':provider has been activated.', ['provider' => $provider->name]));
        } else {
            Toast::make(__(':provider has been deactivated.', ['provider' => $provider->name]));
        }

        return back();
    }
}
