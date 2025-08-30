<?php

namespace App\View\Models;

use Illuminate\Pagination\LengthAwarePaginator;
use StackTrace\Ui\ViewModel;

class Paginator extends ViewModel
{
    public function __construct(
        protected LengthAwarePaginator $paginator
    ) {}

    public function toView(): array
    {
        return $this->paginator->toArray();
    }

    public static function make(LengthAwarePaginator $paginator): static
    {
        return new static($paginator);
    }
}
