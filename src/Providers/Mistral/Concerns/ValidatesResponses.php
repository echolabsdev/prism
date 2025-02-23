<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Mistral\Concerns;

use EchoLabs\Prism\Exceptions\PrismException;

trait ValidatesResponses
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function validateResponse(array $data): void
    {
        if (! $data || data_get($data, 'object') === 'error') {
            throw PrismException::providerResponseError(vsprintf(
                'Mistral Error: [%s] %s',
                [
                    data_get($data, 'type', 'unknown'),
                    data_get($data, 'message', 'unknown'),
                ]
            ));
        }
    }
}
