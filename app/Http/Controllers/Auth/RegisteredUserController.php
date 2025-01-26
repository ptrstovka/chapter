<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'invitation' => ['required', 'string', 'max:255'],
        ]);

        /** @var Invitation $invitation */
        $invitation = Invitation::query()->firstWhere('code', Str::lower($request->input('invitation')));

        if (is_null($invitation) || ! $invitation->isValid()) {
            throw ValidationException::withMessages([
                'invitation' => 'The invitation code is invalid.',
            ]);
        }

        $invitationId = $invitation->id;

        $user = DB::transaction(function () use ($request, $invitationId) {
            $invitation = Invitation::query()
                ->where('id', $invitationId)
                ->lockForUpdate()
                ->first();

            if (is_null($invitation) || ! $invitation->isValid()) {
                throw ValidationException::withMessages([
                    'invitation' => 'The invitation code is invalid.',
                ]);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_admin' => false,
            ]);

            $invitation->usedBy()->associate($user);
            $invitation->used_at = now();
            $invitation->save();

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('home', absolute: false));
    }
}
