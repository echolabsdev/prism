<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Ollama\Concerns;

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Providers\Ollama\Maps\FinishReasonMap;

trait MapsFinishReason
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function mapFinishReason(array $data): FinishReason
    {
        if (! empty(data_get($data, 'message.tool_calls'))) {
            return FinishReason::ToolCalls;
        }

        return FinishReasonMap::map(data_get($data, 'done_reason', ''));
    }
}
