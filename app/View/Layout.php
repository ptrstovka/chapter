<?php

namespace App\View;

use StackTrace\Ui\ViewModel;

abstract class Layout extends ViewModel
{
    public function __construct(
        protected array $props
    ) {}

    /**
     * Get the layout props.
     */
    abstract public function toLayout(): array;

    public function toView(): array
    {
        return [
            ...$this->props,
            ...$this->toLayout(),
        ];
    }
}
