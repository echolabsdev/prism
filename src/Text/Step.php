<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Text;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Meta;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
use EchoLabs\Prism\ValueObjects\Usage;

readonly class Step
{
    /**
     * @param  ToolCall[]  $toolCalls
     * @param  ToolResult[]  $toolResults
     * @param  Message[]  $messages
     * @param  SystemMessage[]  $systemPrompts
     * @param  array<string,mixed>  $additionalContent
     */
    public function __construct(
        public readonly string $text,
        public readonly FinishReason $finishReason,
        public readonly array $toolCalls,
        public readonly array $toolResults,
        public readonly Usage $usage,
        public readonly Meta $meta,
        public readonly array $messages,
        public readonly array $systemPrompts,
        public readonly array $additionalContent = []
    ) {}
}
