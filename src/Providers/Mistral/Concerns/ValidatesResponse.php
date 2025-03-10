<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\Mistral\Concerns;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Exceptions\PrismRateLimitedException;
use PrismPHP\Prism\ValueObjects\ProviderRateLimit;

trait ValidatesResponse
{
    protected function validateResponse(Response $response): void
    {
        if ($response->getStatusCode() === 429) {
            throw PrismRateLimitedException::make(
                rateLimits: $this->processRateLimits($response),
                retryAfter: null
            );
        }

        $data = $response->json();

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

    /**
     * @return ProviderRateLimit[]
     */
    protected function processRateLimits(Response $response): array
    {
        return [
            new ProviderRateLimit(
                name: 'tokens',
                limit: (int) $response->header('ratelimitbysize-limit'),
                remaining: (int) $response->header('ratelimitbysize-remaining'),
                resetsAt: Carbon::now()->addSeconds((int) $response->header('ratelimitbysize-reset')),
            ),
        ];
    }
}
