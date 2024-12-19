<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects;

class Usage
{
    public function __construct(
        public readonly int $promptTokens,
        public readonly int $completionTokens,
        public readonly ?int $cacheWriteInputTokens = null,
        public readonly ?int $cacheReadInputTokens = null
    ) {}
}
