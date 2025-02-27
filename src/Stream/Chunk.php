<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Stream;

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;

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
