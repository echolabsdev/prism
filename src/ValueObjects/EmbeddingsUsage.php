<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects;

class EmbeddingsUsage
{
    public function __construct(
        public readonly int $tokens
    ) {}
}
