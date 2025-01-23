<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Handlers;

use EchoLabs\Prism\Contracts\PrismRequest;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
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
}
