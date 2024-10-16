<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Parameters;

use EchoLabs\Prism\Contracts\Parameter;

class ArrayParameter implements Parameter
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly Parameter $item,
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
