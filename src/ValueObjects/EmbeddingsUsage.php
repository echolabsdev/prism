<?php

declare(strict_types=1);

namespace PrismPHP\Prism\ValueObjects;

readonly class EmbeddingsUsage
{
    public function __construct(
        public ?int $tokens
    ) {}
}
