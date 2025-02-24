<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Cohere\Maps;

use EchoLabs\Prism\Enums\FinishReason;

class FinishReasonMap
{
    public static function map(?string $reason): FinishReason
    {
        return match ($reason) {
            'COMPLETE', 'STOP_SEQUENCE' => FinishReason::Stop,
            'MAX_TOKENS' => FinishReason::Length,
            'ERROR' => FinishReason::Error,
            'TOOL_CALL' => FinishReason::ToolCalls,
            default => FinishReason::Other,
        };
    }
}
