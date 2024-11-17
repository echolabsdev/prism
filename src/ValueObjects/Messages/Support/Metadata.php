<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages\Support;

use ArrayAccess;
use EchoLabs\Prism\Enums\Provider;

class Metadata implements ArrayAccess
{
    /**
     * @param  array<Provider, array<string, mixed>>  $metadata
     */
    public function __construct(
        public readonly array $metadata = []
    ) {}

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->metadata[$offset]);
    }

    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->metadata[$offset] ?? null;
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException('Metadata is immutable');
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException('Metadata is immutable');
    }
}
