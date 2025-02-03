<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Cohere;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Embeddings\Request as EmbeddingRequest;
use EchoLabs\Prism\Embeddings\Response as EmbeddingResponse;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Cohere\Handlers\Embeddings;
use EchoLabs\Prism\Providers\Cohere\Handlers\Structured;
use EchoLabs\Prism\Providers\Cohere\Handlers\Text;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Override;
use SensitiveParameter;

/**
 * @see https://docs.cohere.com/
 */
class Cohere implements Provider
{
    /**
     * @param array<string, string> $embedConfig
     */
    public function __construct(
        #[SensitiveParameter] public readonly string $apiKey,
        public readonly string                       $url,
        public readonly array                        $embedConfig,
    )
    {
    }

    /**
     * @throws PrismException
     */
    #[Override]
    public function text(TextRequest $request): ProviderResponse
    {
        $handler = new Text($this->client($request->clientOptions, $request->clientRetry));

        return $handler->handle($request);
    }

    /**
     * @throws Exception
     */
    #[Override]
    public function structured(StructuredRequest $request): ProviderResponse
    {
        $handler = new Structured($this->client($request->clientOptions, $request->clientRetry));

        return $handler->handle($request);
    }

    /**
     * @throws PrismException
     */
    #[Override]
    public function embeddings(EmbeddingRequest $request): EmbeddingResponse
    {
        $handler = new Embeddings($this->embedConfig, $this->client($request->clientOptions, $request->clientRetry));

        return $handler->handle($request);
    }

    /**
     * @param array<string, mixed> $options
     * @param array<mixed> $retry
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
