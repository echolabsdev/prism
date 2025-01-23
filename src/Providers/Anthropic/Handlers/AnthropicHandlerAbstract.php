<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Handlers;

use EchoLabs\Prism\Contracts\PrismRequest;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Exceptions\PrismRateLimitedException;
use EchoLabs\Prism\ValueObjects\ProviderRateLimit;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

abstract class AnthropicHandlerAbstract
{
    protected PrismRequest $request;

    protected Response $httpResponse;

    public function __construct(protected PendingRequest $client) {}

    /**
     * @return array<string, mixed>
     */
    abstract public static function buildHttpRequestPayload(PrismRequest $request): array;

    public function handle(PrismRequest $request): ProviderResponse
    {
        $this->request = $request;

        try {
            $this->prepareRequest();
            $this->httpResponse = $this->sendRequest();
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($this->request->model, $e); // @phpstan-ignore property.notFound
        }

        $this->handleResponseErrors();

        return $this->buildProviderResponse();
    }

    abstract protected function prepareRequest(): void;

    abstract protected function buildProviderResponse(): ProviderResponse;

    protected function sendRequest(): Response
    {
        return $this->client->post(
            'messages',
            static::buildHttpRequestPayload($this->request)
        );
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
                limit: data_get($fields, 'limit')
                    ? (int) data_get($fields, 'limit')
                    : null,
                remaining: data_get($fields, 'remaining')
                    ? (int) data_get($fields, 'remaining')
                    : null,
                resetsAt: data_get($fields, 'reset') ? new Carbon($resets_at) : null
            );
        }));
    }
}
