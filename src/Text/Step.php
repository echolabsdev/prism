<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Text;

use PrismPHP\Prism\Contracts\Message;
use PrismPHP\Prism\Enums\FinishReason;
use PrismPHP\Prism\ValueObjects\Messages\SystemMessage;
use PrismPHP\Prism\ValueObjects\Meta;
use PrismPHP\Prism\ValueObjects\ToolCall;
use PrismPHP\Prism\ValueObjects\ToolResult;
use PrismPHP\Prism\ValueObjects\Usage;

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
