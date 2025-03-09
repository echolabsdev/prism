<?php

namespace PrismPHP\Prism\Providers\Gemini\Concerns;

use Illuminate\Http\Client\Response;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Exceptions\PrismRateLimitedException;

trait ValidatesResponse
{
    protected function validateResponse(Response $response): void
    {
        if ($response->getStatusCode() === 429) {
            throw new PrismRateLimitedException([]);
        }

        $data = $response->json();

        if (! $data || data_get($data, 'error')) {
            throw PrismException::providerResponseError(vsprintf(
                'Gemini Error: [%s] %s',
                [
                    data_get($data, 'error.code', 'unknown'),
                    data_get($data, 'error.message', 'unknown'),
                ]
            ));
        }
    }
}
