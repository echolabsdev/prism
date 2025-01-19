<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects;

use EchoLabs\Prism\Enums\FinishReason;

class ProviderResponse
{
    /**
     * @param  array<int, ToolCall>  $toolCalls
     */
    public function __construct(
        public readonly string $text,
        public readonly array $toolCalls,
        public readonly Usage $usage,
        public readonly FinishReason $finishReason,
        public readonly ResponseMeta $responseMeta,
    ) {}
}
