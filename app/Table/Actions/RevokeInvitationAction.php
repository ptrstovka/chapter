<?php


namespace App\Table\Actions;


use App\Models\Invitation;
use Illuminate\Support\Facades\Auth;
use StackTrace\Ui\Selection;
use StackTrace\Ui\Table\Actions\Action;

class RevokeInvitationAction extends Action
{
    protected bool $destructive = true;

    public function getLabel(): ?string
    {
        return __('Revoke');
    }

    protected function getTitle(): ?string
    {
        return __('Revoke Invitation');
    }

    protected function getDescription(): ?string
    {
        return __('Are you sure you want to revoke this invitation?');
    }

    protected function getConfirmLabel(): string
    {
        return __('Revoke');
    }

    public function authorize(): bool
    {
        if ($user = Auth::user()) {
            return $user->is_admin;
        }

        return false;
    }

    public function handle(Selection $selection): void
    {
        Invitation::query()
            ->whereIn('id', $selection->all())
            ->eachById(function (Invitation $invitation) {
                if ($invitation->isValid()) {
                    $invitation->revoke();
                }
            });
    }
}
