<?php

namespace EchoLabs\Prism\Providers\Cohere\Handlers;

use EchoLabs\Prism\ValueObjects\ToolCall;

/**
 * Helper trait for handlers
 */
trait Handler
{
    /**
     * @param array<int, array<string, mixed>> $toolCalls
     * @return array<int, ToolCall>
     */
    protected function mapToolCalls(array $toolCalls): array
    {
        return array_map(fn(array $toolCall): ToolCall => new ToolCall(
            id: data_get($toolCall, 'id'),
            name: data_get($toolCall, 'function.name'),
            arguments: data_get($toolCall, 'function.arguments'),
        ), $toolCalls);
    }
}