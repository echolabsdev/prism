<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Gemini\Maps;

use EchoLabs\Prism\Enums\FinishReason;

class FinishReasonMap
{
    public static function map(?string $reason): FinishReason
    {
        return match ($reason) {
            'STOP' => FinishReason::Stop,
            'MAX_TOKENS' => FinishReason::Length,
            'SAFETY', 'BLOCKLIST', 'PROHIBITED_CONTENT', 'SPII', 'MALFORMED_FUNCTION_CALL' => FinishReason::ContentFilter,
            'RECITATION' => FinishReason::ContentFilter,
            'LANGUAGE' => FinishReason::Other,
            'FINISH_REASON_UNSPECIFIED', 'OTHER', null => FinishReason::Other,
            default => FinishReason::Other,
        };
    }
} 
