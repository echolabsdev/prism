<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Schema;

use EchoLabs\Prism\Contracts\Parameter;

class BooleanSchema implements Parameter
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
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
            'type' => 'boolean',
        ];
    }
}
