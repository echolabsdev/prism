<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\Anthropic\Maps;

use PrismPHP\Prism\Enums\FinishReason;

class FinishReasonMap
{
    public static function map(string $reason): FinishReason
    {
        return match ($reason) {
            'end_turn', 'stop_sequence' => FinishReason::Stop,
            'tool_use' => FinishReason::ToolCalls,
            'max_tokens' => FinishReason::Length,
            default => FinishReason::Unknown,
        };
    }
}
