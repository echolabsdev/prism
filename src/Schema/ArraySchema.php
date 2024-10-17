<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Schema;

use EchoLabs\Prism\Contracts\Schema;

class ArraySchema implements Schema
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly Schema $item,
    ) {}

    #[\Override]
    public function name(): string
    {
        return $this->name;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'type' => 'array',
            'items' => $this->item->toArray(),
        ];
    }
}
