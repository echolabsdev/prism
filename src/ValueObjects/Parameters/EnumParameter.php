<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Parameters;

use EchoLabs\Prism\Contracts\Parameter;

class EnumParameter implements Parameter
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array $options,
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
            'enum' => $this->options,
        ];
    }
}
