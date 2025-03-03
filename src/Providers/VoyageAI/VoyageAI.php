<?php

namespace EchoLabs\Prism\Providers\VoyageAI;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Embeddings\Request as EmbeddingRequest;
use EchoLabs\Prism\Embeddings\Response as EmbeddingsResponse;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Stream\Request as StreamRequest;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Structured\Response as StructuredResponse;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\Text\Response as TextResponse;
use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class VoyageAI implements Provider
{
    public function __construct(
        #[\SensitiveParameter] protected string $apiKey,
        protected string $baseUrl
    ) {}

    #[\Override]
    public function text(TextRequest $request): TextResponse
    {
        throw PrismException::unsupportedProviderAction(__METHOD__, class_basename($this));
    }

    #[\Override]
    public function structured(StructuredRequest $request): StructuredResponse
    {
        throw PrismException::unsupportedProviderAction(__METHOD__, class_basename($this));
    }

    #[\Override]
    public function embeddings(EmbeddingRequest $request): EmbeddingsResponse
    {
        $handler = new Embeddings($this->client(
            $request->clientOptions(),
            $request->clientRetry()
        ));

        return $handler->handle($request);
    }

    #[\Override]
    public function stream(StreamRequest $request): Generator
    {
        throw PrismException::unsupportedProviderAction(__METHOD__, class_basename($this));
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<mixed>  $retry
     */
    protected function client(array $options = [], array $retry = []): PendingRequest
    {
        return Http::withToken($this->apiKey)
            ->withOptions($options)
            ->retry(...$retry)
            ->baseUrl($this->baseUrl);
    }
}
