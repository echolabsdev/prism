<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects;

class ToolResult
{
    /**
     * @param  array<string, mixed>  $args
     * @param  array<string, mixed>  $result
     */
    public function __construct(
        public readonly string $toolCallId,
        public readonly string $toolName,
        public readonly array $args,
        public readonly int|float|string|array|null $result,
    ) {}
}
