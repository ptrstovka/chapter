<?php


namespace App\Table\Columns;


use Closure;
use StackTrace\Ui\Table\Column;

class Toggle extends Column
{
    protected string|Closure|null $url = null;

    protected string|Closure $method = 'post';

    protected string|Closure $field = 'value';

    public function url(string|Closure|null $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function method(string|Closure $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function field(string|Closure $field): static
    {
        $this->field = $field;

        return $this;
    }

    public function toView($value, $resource): array
    {
        return [
            'value' => $value,
            'url' => $this->url instanceof Closure ? call_user_func($this->url, $resource) : $this->url,
            'method' => $this->method instanceof Closure ? call_user_func($this->method, $resource) : $this->method,
            'field' => $this->field instanceof Closure ? call_user_func($this->field, $resource) : $this->field,
        ];
    }

    public function component(): string
    {
        return 'DataTable::Columns/Toggle';
    }
}
