<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Handlers;

use EchoLabs\Prism\Contracts\PrismRequest;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Exceptions\PrismProviderOverloadedException;
use EchoLabs\Prism\Exceptions\PrismRateLimitedException;
use EchoLabs\Prism\Exceptions\PrismRequestTooLargeException;
use EchoLabs\Prism\Providers\Anthropic\ValueObjects\MessagePartWithCitations;
use EchoLabs\Prism\ValueObjects\ProviderRateLimit;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

abstract class AnthropicHandlerAbstract
{
    protected Response $httpResponse;

    public function __construct(protected PendingRequest $client, protected PrismRequest $request) {}

    /**
     * @return array<string, mixed>
     */
    abstract public static function buildHttpRequestPayload(PrismRequest $request): array;

    protected function sendRequest(): void
    {
        try {
            $this->httpResponse = $this->client->post(
                'messages',
                static::buildHttpRequestPayload($this->request)
            );
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($this->request->model(), $e);
        }

        $this->handleResponseErrors();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractText(array $data): string
    {
        return array_reduce(data_get($data, 'content', []), function (string $text, array $content): string {
            if (data_get($content, 'type') === 'text') {
                $text .= data_get($content, 'text');
            }

            return $text;
        }, '');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return null|MessagePartWithCitations[]
     */
    protected function extractCitations(array $data): ?array
    {
        if (array_filter(data_get($data, 'content.*.citations')) === []) {
            return null;
        }

        return Arr::map(data_get($data, 'content', []), fn ($contentBlock): \EchoLabs\Prism\Providers\Anthropic\ValueObjects\MessagePartWithCitations => MessagePartWithCitations::fromContentBlock($contentBlock));
    }

    protected function handleResponseErrors(): void
    {
        if ($this->httpResponse->getStatusCode() === 429) {
            throw PrismRateLimitedException::make(
                rateLimits: array_values($this->processRateLimits()),
                retryAfter: $this->httpResponse->hasHeader('retry-after')
                    ? (int) $this->httpResponse->getHeader('retry-after')[0]
                    : null
            );
        }

        if ($this->httpResponse->getStatusCode() === 529) {
            throw PrismProviderOverloadedException::make(Provider::Anthropic);
        }

        if ($this->httpResponse->getStatusCode() === 413) {
            throw PrismRequestTooLargeException::make(Provider::Anthropic);
        }

        $data = $this->httpResponse->json();

        if (data_get($data, 'type') === 'error') {
            throw PrismException::providerResponseError(vsprintf(
                'Anthropic Error: [%s] %s',
                [
                    data_get($data, 'error.type', 'unknown'),
                    data_get($data, 'error.message'),
                ]
            ));
        }
    }

    /**
     * @return ProviderRateLimit[]
     */
    protected function processRateLimits(): array
    {
        $rate_limits = [];

        foreach ($this->httpResponse->getHeaders() as $headerName => $headerValues) {
            if (Str::startsWith($headerName, 'anthropic-ratelimit-') === false) {
                continue;
            }

            $limit_name = Str::of($headerName)->after('anthropic-ratelimit-')->beforeLast('-')->toString();

            $field_name = Str::of($headerName)->afterLast('-')->toString();

            $rate_limits[$limit_name][$field_name] = $headerValues[0];
        }

        return array_values(Arr::map($rate_limits, function ($fields, $limit_name): ProviderRateLimit {
            $resets_at = data_get($fields, 'reset');

            return new ProviderRateLimit(
                name: $limit_name,
                limit: data_get($fields, 'limit') !== null
                    ? (int) data_get($fields, 'limit')
                    : null,
                remaining: data_get($fields, 'remaining') !== null
                    ? (int) data_get($fields, 'remaining')
                    : null,
                resetsAt: data_get($fields, 'reset') !== null ? new Carbon($resets_at) : null
            );
        }));
    }
}
