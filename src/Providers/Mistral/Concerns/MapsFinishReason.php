<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Mistral\Concerns;

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Providers\Mistral\Maps\FinishReasonMap;

trait MapsFinishReason
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function mapFinishReason(array $data): FinishReason
    {
        return FinishReasonMap::map(data_get($data, 'choices.0.finish_reason', ''));
    }
}
