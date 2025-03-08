<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Stream;

use PrismPHP\Prism\Enums\FinishReason;
use PrismPHP\Prism\ValueObjects\ToolCall;
use PrismPHP\Prism\ValueObjects\ToolResult;

readonly class Chunk
{
    /**
     * @param  ToolCall[]  $toolCalls
     * @param  ToolResult[]  $toolResults
     */
    public function __construct(
        public string $text,
        public array $toolCalls = [],
        public array $toolResults = [],
        public ?FinishReason $finishReason = null,
    ) {}
}
