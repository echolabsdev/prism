<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\OpenAI\Concerns;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Exceptions\PrismRateLimitedException;
use PrismPHP\Prism\ValueObjects\ProviderRateLimit;

trait ValidatesResponses
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

        if (! $data || data_get($data, 'error')) {
            throw PrismException::providerResponseError(vsprintf(
                'OpenAI Error:  [%s] %s',
                [
                    data_get($data, 'error.type', 'unknown'),
                    data_get($data, 'error.message', 'unknown'),
                ]
            ));
        }
    }

    /**
     * @return ProviderRateLimit[]
     */
    protected function processRateLimits(Response $response): array
    {
        $limitHeaders = array_filter($response->getHeaders(), fn ($headerName) => Str::startsWith($headerName, 'x-ratelimit-'), ARRAY_FILTER_USE_KEY);

        $rateLimits = [];

        foreach ($limitHeaders as $headerName => $headerValues) {
            $limitName = Str::of($headerName)->afterLast('-')->toString();
            $fieldName = Str::of($headerName)->after('x-ratelimit-')->beforeLast('-')->toString();

            $rateLimits[$limitName][$fieldName] = $headerValues[0];
        }

        return array_values(Arr::map($rateLimits, function ($fields, $limitName): ProviderRateLimit {
            $resetsAt = data_get($fields, 'reset');

            $resetMinutes = str_contains($resetsAt, 'm')
                ? Str::of($resetsAt)->before('m')->toString()
                : 0;

            $resetSeconds = str_contains($resetsAt, 'm')
                ? Str::of($resetsAt)->after('m')->before('s')->toString()
                : Str::of($resetsAt)->before('s')->toString();

            return new ProviderRateLimit(
                name: $limitName,
                limit: data_get($fields, 'limit') !== null
                    ? (int) data_get($fields, 'limit')
                    : null,
                remaining: data_get($fields, 'remaining') !== null
                    ? (int) data_get($fields, 'remaining')
                    : null,
                resetsAt: data_get($fields, 'reset') !== null
                    ? Carbon::now()->addMinutes((int) $resetMinutes)->addSeconds((int) $resetSeconds)
                    : null
            );
        }));
    }
}
