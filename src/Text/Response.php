<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Text;

use Illuminate\Support\Collection;
use PrismPHP\Prism\Contracts\Message;
use PrismPHP\Prism\Enums\FinishReason;
use PrismPHP\Prism\ValueObjects\Meta;
use PrismPHP\Prism\ValueObjects\ToolCall;
use PrismPHP\Prism\ValueObjects\ToolResult;
use PrismPHP\Prism\ValueObjects\Usage;

readonly class Response
{
    /**
     * @param  Collection<int, Step>  $steps
     * @param  Collection<int, Message>  $responseMessages
     * @param  ToolCall[]  $toolCalls
     * @param  ToolResult[]  $toolResults
     * @param  Collection<int, Message>  $messages
     * @param  array<string,mixed>  $additionalContent
     */
    public function __construct(
        public readonly Collection $steps,
        public readonly Collection $responseMessages,
        public readonly string $text,
        public readonly FinishReason $finishReason,
        public readonly array $toolCalls,
        public readonly array $toolResults,
        public readonly Usage $usage,
        public readonly Meta $meta,
        public readonly Collection $messages,
        public readonly array $additionalContent = []
    ) {}
}
