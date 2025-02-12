<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects;

use EchoLabs\Prism\Enums\FinishReason;

readonly class ProviderResponse
{
    /**
     * @param  array<int, ToolCall>  $toolCalls
     * @param  array<string,mixed>  $additionalContent
     */
    public function __construct(
        public readonly string $text,
        public readonly array $toolCalls,
        public readonly Usage $usage,
        public readonly FinishReason $finishReason,
        public readonly ResponseMeta $responseMeta,
        public readonly array $additionalContent = []
    ) {}
}
