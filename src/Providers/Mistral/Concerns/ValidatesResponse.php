<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Mistral\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Http\Client\Response;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\ValueObjects\ProviderRateLimit;
use PrismPHP\Prism\Exceptions\PrismRateLimitedException;

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
