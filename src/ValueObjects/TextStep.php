<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\FinishReason;

class TextStep
{
    /**
     * @param  array<int, ToolCall>  $toolCalls
     * @param  array<int, ToolResult>  $toolResults
     * @param  array{id: string, model: string}  $response
     * @param  array<int, Message>  $messages
     */
    public function __construct(
        public readonly string $text,
        public readonly FinishReason $finishReason,
        public readonly array $toolCalls,
        public readonly array $toolResults,
        public readonly Usage $usage,
        public readonly array $response,
        public readonly array $messages = [],
    ) {}
}
