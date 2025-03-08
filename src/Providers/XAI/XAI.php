<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\XAI;

use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use PrismPHP\Prism\Contracts\Provider;
use PrismPHP\Prism\Embeddings\Request as EmbeddingRequest;
use PrismPHP\Prism\Embeddings\Response as EmbeddingResponse;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Providers\XAI\Handlers\Text;
use PrismPHP\Prism\Stream\Request as StreamRequest;
use PrismPHP\Prism\Structured\Request as StructuredRequest;
use PrismPHP\Prism\Structured\Response as StructuredResponse;
use PrismPHP\Prism\Text\Request as TextRequest;
use PrismPHP\Prism\Text\Response as TextResponse;

readonly class XAI implements Provider
{
    public function __construct(
        #[\SensitiveParameter] public string $apiKey,
        public string $url,
    ) {}

    #[\Override]
    public function text(TextRequest $request): TextResponse
    {
        $handler = new Text($this->client($request->clientOptions(), $request->clientRetry()));

        return $handler->handle($request);
    }

    #[\Override]
    public function structured(StructuredRequest $request): StructuredResponse
    {
        throw PrismException::unsupportedProviderAction(__METHOD__, class_basename($this));
    }

    #[\Override]
    public function embeddings(EmbeddingRequest $request): EmbeddingResponse
    {
        throw PrismException::unsupportedProviderAction(__METHOD__, class_basename($this));
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
        return Http::withHeaders(array_filter([
            'Authorization' => $this->apiKey !== '' && $this->apiKey !== '0' ? sprintf('Bearer %s', $this->apiKey) : null,
        ]))
            ->withOptions($options)
            ->retry(...$retry)
            ->baseUrl($this->url);
    }
}
