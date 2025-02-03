<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
use EchoLabs\Prism\ValueObjects\Usage;

class Step
{
    /**
     * @param  ToolCall[]  $toolCalls
     * @param  ToolResult[]  $toolResults
     * @param  array<mixed>  $object
     * @param  Message[]  $messages
     */
    public function __construct(
        public readonly string $text,
        public readonly ?array $object,
        public readonly FinishReason $finishReason,
        public readonly array $toolCalls,
        public readonly array $toolResults,
        public readonly Usage $usage,
        public readonly ResponseMeta $responseMeta,
        public readonly array $messages,
    ) {}
}
