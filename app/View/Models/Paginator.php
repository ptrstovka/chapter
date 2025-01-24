<?php


namespace App\View\Models;


use Illuminate\Pagination\LengthAwarePaginator;

class Paginator extends Model
{
    public function __construct(
        protected LengthAwarePaginator $paginator
    ) { }

    public function toView(): array
    {
        return $this->paginator->toArray();
    }

    public static function make(LengthAwarePaginator $paginator): static
    {
        return new static($paginator);
    }
}
