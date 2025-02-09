<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects;

use EchoLabs\Prism\Enums\FinishReason;

readonly class ProviderResponse
{
    /**
     * @param  array<int, ToolCall>  $toolCalls
     */
    public function __construct(
        public string $text,
        public array $toolCalls,
        public Usage $usage,
        public FinishReason $finishReason,
        public ResponseMeta $responseMeta,
    ) {}
}
