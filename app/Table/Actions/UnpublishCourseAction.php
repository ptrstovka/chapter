<?php


namespace App\Table\Actions;


use Illuminate\Support\Facades\Auth;
use StackTrace\Ui\Selection;
use StackTrace\Ui\Table\Actions\Action;

class UnpublishCourseAction extends Action
{
    protected bool $destructive = true;

    public function getLabel(): ?string
    {
        return __('Unpublish');
    }

    protected function getTitle(): ?string
    {
        return __('Unpublish Course');
    }

    protected function getDescription(): ?string
    {
        return __('Are you sure you want to unpublish this course? Your audience won\'t be able to access this course anymore.');
    }

    protected function getConfirmLabel(): string
    {
        return __('Unpublish');
    }

    public function authorize(): bool
    {
        if ($user = Auth::user()) {
            return $user->is_admin || $user->isAuthor();
        }

        return false;
    }

    public function handle(Selection $selection): void
    {
        // TODO: Add unpublish
    }
}
