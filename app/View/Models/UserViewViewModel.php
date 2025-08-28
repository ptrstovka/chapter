<?php

namespace App\View\Models;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use StackTrace\Ui\ViewModel;

class UserViewViewModel extends ViewModel
{
    public function __construct(
        protected User $user
    ) {}

    public function toView(): array
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'emailVerifiedAt' => $this->user->email_verified_at,
            'can' => [
                'accessStudio' => Gate::allows('accessStudio'),
            ],
        ];
    }

    /**
     * Create new view model instance.
     */
    public static function from(?User $user): ?static
    {
        return $user ? new static($user) : null;
    }
}
