<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects;

readonly class EmbeddingsUsage
{
    public function __construct(
        public null|int $tokens
    ) {}
}
