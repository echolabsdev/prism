<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\XAI\Concerns;

use EchoLabs\Prism\Exceptions\PrismException;

trait ValidatesResponses
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function validateResponse(array $data): void
    {
        if (! $data || data_get($data, 'error')) {
            throw PrismException::providerResponseError(vsprintf(
                'XAI Error:  [%s] %s',
                [
                    data_get($data, 'error.type', 'unknown'),
                    data_get($data, 'error.message', 'unknown'),
                ]
            ));
        }
    }
}
