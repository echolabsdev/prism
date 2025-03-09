<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\XAI\Concerns;

use Illuminate\Http\Client\Response;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Exceptions\PrismRateLimitedException;

trait ValidatesResponses
{
    protected function validateResponse(Response $response): void
    {
        if ($response->getStatusCode() === 429) {
            throw new PrismRateLimitedException([]);
        }

        $data = $response->json();

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
