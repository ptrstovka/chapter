<?php


namespace App\Table\Actions;


use Illuminate\Support\Facades\Auth;
use StackTrace\Ui\Selection;
use StackTrace\Ui\Table\Actions\Action;

class PublishCourseAction extends Action
{
    public function getLabel(): ?string
    {
        return __('Publish');
    }

    protected function getTitle(): ?string
    {
        return __('Publish Course');
    }

    protected function getDescription(): ?string
    {
        return __('Are you sure you want to publish this course? The course will be accessible to your audience.');
    }

    protected function getConfirmLabel(): string
    {
        return __('Publish');
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
        // TODO: Add publish
    }
}
