<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Embeddings\Request as EmbeddingRequest;
use EchoLabs\Prism\Embeddings\Response as EmbeddingResponse;
use EchoLabs\Prism\Providers\Anthropic\Handlers\Structured;
use EchoLabs\Prism\Providers\Anthropic\Handlers\Text;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

readonly class Anthropic implements Provider
{
    public function __construct(
        #[\SensitiveParameter] public string $apiKey,
        public string $apiVersion,
        public ?string $betaFeatures = null
    ) {}

    #[\Override]
    public function text(TextRequest $request, int $currentStep): ProviderResponse
    {
        $handler = new Text($this->client(
            $request->clientOptions,
            $request->clientRetry
        ));

        return $handler->handle($request);
    }

    #[\Override]
    public function structured(StructuredRequest $request): ProviderResponse
    {
        $handler = new Structured($this->client(
            $request->clientOptions,
            $request->clientRetry
        ));

        return $handler->handle($request);
    }

    #[\Override]
    public function embeddings(EmbeddingRequest $request): EmbeddingResponse
    {
        throw new \Exception(sprintf('%s does not support embeddings', class_basename($this)));
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<mixed>  $retry
     */
    protected function client(array $options = [], array $retry = []): PendingRequest
    {
        return Http::withHeaders(array_filter([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->apiVersion,
            'anthropic-beta' => $this->betaFeatures,
        ]))
            ->withOptions($options)
            ->retry(...$retry)
            ->baseUrl('https://api.anthropic.com/v1');
    }
}
