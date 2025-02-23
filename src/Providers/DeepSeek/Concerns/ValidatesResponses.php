<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\DeepSeek\Concerns;

use EchoLabs\Prism\Exceptions\PrismException;

trait ValidatesResponses
{
    /**
     * @param array<string, mixed> $data
     */
    protected function validateResponse(array $data): void
    {
        if (! $data) {
            throw PrismException::providerResponseError('DeepSeek Error: Empty response');
        }
    }
} 
