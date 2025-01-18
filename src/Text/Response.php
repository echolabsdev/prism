<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Text;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Support\Collection;

class Response
{
    /**
     * @param  Collection<int, Step>  $steps
     * @param  Collection<int, Message>  $responseMessages
     * @param  ToolCall[]  $toolCalls
     * @param  ToolResult[]  $toolResults
     * @param  Collection<int, Message>  $messages
     * @param  array{id: string, model: string}  $response
     */
    public function __construct(
        public readonly Collection $steps,
        public readonly Collection $responseMessages,
        public readonly string $text,
        public readonly FinishReason $finishReason,
        public readonly array $toolCalls,
        public readonly array $toolResults,
        public readonly Usage $usage,
        public readonly array $response,
        public readonly Collection $messages,
    ) {}
}
