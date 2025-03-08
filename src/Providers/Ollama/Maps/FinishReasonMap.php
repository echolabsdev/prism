<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\Ollama\Maps;

use PrismPHP\Prism\Enums\FinishReason;

class FinishReasonMap
{
    public static function map(string $reason): FinishReason
    {
        return match ($reason) {
            'stop', => FinishReason::Stop,
            'tool_calls' => FinishReason::ToolCalls,
            'length' => FinishReason::Length,
            default => FinishReason::Unknown,
        };
    }
}
