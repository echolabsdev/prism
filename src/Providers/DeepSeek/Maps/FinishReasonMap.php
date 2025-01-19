<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\DeepSeek\Maps;

use EchoLabs\Prism\Enums\FinishReason;

class FinishReasonMap
{
    public static function map(string $reason): FinishReason
    {
        return match ($reason) {
            'stop', => FinishReason::Stop,
            'tool_calls' => FinishReason::ToolCalls,
            'length' => FinishReason::Length,
            'content_filter' => FinishReason::ContentFilter,
            default => FinishReason::Unknown,
        };
    }
}
