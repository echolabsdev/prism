<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects;

// {
//   toolCallId: 'call_fnT5I1VQvJGH2AGLxG5lPKOH',
//   toolName: 'search',
//   args: { query: 'Detroit Tigers game time today' },
//   result: {
//     query: 'Detroit Tigers game time today',
//     result: 'The tigers game is at 3pm today in Detroit'
//   }
// },

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
        public readonly int|float|string|array $result,
    ) {}
}
