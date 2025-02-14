<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\XAI;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Embeddings\Request as EmbeddingRequest;
use EchoLabs\Prism\Embeddings\Response as EmbeddingResponse;
use EchoLabs\Prism\Providers\XAI\Handlers\Text;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

readonly class XAI implements Provider
{
    public function __construct(
        #[\SensitiveParameter] public string $apiKey,
        public string $url,
    ) {}

    #[\Override]
    public function text(TextRequest $request, int $currentStep): ProviderResponse
    {
        $handler = new Text($this->client($request->clientOptions, $request->clientRetry));

        return $handler->handle($request);
    }

    #[\Override]
    public function structured(StructuredRequest $request): ProviderResponse
    {
        throw new \Exception(sprintf('%s does not support structured mode', class_basename($this)));
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
            'Authorization' => $this->apiKey !== '' && $this->apiKey !== '0' ? sprintf('Bearer %s', $this->apiKey) : null,
        ]))
            ->withOptions($options)
            ->retry(...$retry)
            ->baseUrl($this->url);
    }
}
