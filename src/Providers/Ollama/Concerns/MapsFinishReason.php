<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Ollama\Concerns;

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Providers\Ollama\Maps\FinishReasonMap;
use Illuminate\Support\Fluent;

trait MapsFinishReason
{
    /**
     * @param  Fluent<string, mixed>  $data
     */
    protected function mapFinishReason(Fluent $data): FinishReason
    {
        if (! empty($data->has('message.tool_calls'))) {
            return FinishReason::ToolCalls;
        }

        return FinishReasonMap::map($data->get('done_reason', ''));
    }
}
