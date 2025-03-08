<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Schema;

use PrismPHP\Prism\Concerns\NullableSchema;
use PrismPHP\Prism\Contracts\Schema;

class BooleanSchema implements Schema
{
    use NullableSchema;

    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly bool $nullable = false,
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
            'type' => $this->nullable
                ? $this->castToNullable('boolean')
                : 'boolean',
        ];
    }
}
