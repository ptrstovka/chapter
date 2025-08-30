<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Number;

final readonly class FileListItem implements Arrayable
{
    public function __construct(
        public string|int $id,
        public string $url,
        public string $name,
        public int $size,
        public string $mime,
        public mixed $source = null,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => 'existing',
            'id' => $this->id,
            'url' => $this->url,
            'name' => $this->name,
            'size' => Number::fileSize($this->size),
            'mime' => $this->mime,
        ];
    }
}
