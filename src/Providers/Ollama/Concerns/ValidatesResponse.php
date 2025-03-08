<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\Ollama\Concerns;

use PrismPHP\Prism\Exceptions\PrismException;

trait ValidatesResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function validateResponse(array $data): void
    {
        if (! $data || data_get($data, 'error')) {
            throw PrismException::providerResponseError(sprintf(
                'Ollama Error: %s',
                data_get($data, 'error', 'unknown'),
            ));
        }
    }
}
