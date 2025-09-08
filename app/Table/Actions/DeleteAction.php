<?php

namespace App\Table\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use StackTrace\Ui\Selection;
use StackTrace\Ui\Table\Actions\Action;

class DeleteAction extends Action
{
    protected bool $destructive = true;

    public function __construct(
        protected string $model
    ) {}

    public function getLabel(): ?string
    {
        return __('Delete');
    }

    protected function getTitle(): ?string
    {
        return __('Delete Record');
    }

    protected function getDescription(): ?string
    {
        return __('Are you sure you want to delete this record?');
    }

    protected function getConfirmLabel(): string
    {
        return __('Delete');
    }

    public function authorize(): bool
    {
        return Auth::check();
    }

    public function handle(Selection $selection): void
    {
        $class = $this->model;

        if (class_exists($class) && in_array(Model::class, class_parents($class))) {
            /** @var Model $instance */
            $instance = new $class;

            $instance
                ->newQuery()
                ->whereIn($instance->getKeyName(), $selection->all())
                ->eachById(function (Model $model) {
                    if (Gate::allows('delete', $model)) {
                        $model->delete();
                    }
                });
        }
    }
}
