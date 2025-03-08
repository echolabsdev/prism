<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\Mistral\Concerns;

use PrismPHP\Prism\Enums\FinishReason;
use PrismPHP\Prism\Providers\Mistral\Maps\FinishReasonMap;

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
