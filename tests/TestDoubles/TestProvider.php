<?php

declare(strict_types=1);

namespace Tests\TestDoubles;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Embeddings\Request as EmbeddingRequest;
use EchoLabs\Prism\Embeddings\Response as EmbeddingResponse;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Stream\Request as StreamRequest;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Structured\Response as StructuredResponse;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\Text\Response as TextResponse;
use EchoLabs\Prism\ValueObjects\EmbeddingsUsage;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;
use Generator;

class TestProvider implements Provider
{
    public StructuredRequest|TextRequest|EmbeddingRequest $request;

    /** @var array<string, mixed> */
    public array $clientOptions;

    /** @var array<mixed> */
    public array $clientRetry;

    /** @var array<int, StructuredResponse|TextResponse|EmbeddingResponse> */
    public array $responses = [];

    public $callCount = 0;

    #[\Override]
    public function text(TextRequest $request): TextResponse
    {
        $this->callCount++;

        $this->request = $request;

        return $this->responses[$this->callCount - 1] ?? new TextResponse(
            text: "I'm nyx!",
            steps: collect([]),
            responseMessages: collect([]),
            toolCalls: [],
            toolResults: [],
            usage: new Usage(10, 10),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('123', 'claude-3-5-sonnet-20240620'),
            messages: collect([]),
        );
    }

    #[\Override]
    public function structured(StructuredRequest $request): StructuredResponse
    {
        $this->callCount++;

        $this->request = $request;

        return $this->responses[$this->callCount - 1] ?? new StructuredResponse(
            text: json_encode([]),
            structured: [],
            steps: collect([]),
            responseMessages: collect([]),
            usage: new Usage(10, 10),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('123', 'claude-3-5-sonnet-20240620')
        );
    }

    #[\Override]
    public function embeddings(EmbeddingRequest $request): EmbeddingResponse
    {
        $this->callCount++;

        $this->request = $request;

        return $this->responses[$this->callCount - 1] ?? new EmbeddingResponse(
            embeddings: [],
            usage: new EmbeddingsUsage(10),
        );
    }

    #[\Override]
    public function stream(StreamRequest $request): Generator
    {
        throw PrismException::unsupportedProviderAction(__METHOD__, class_basename($this));
    }

    public function withResponse(StructuredResponse|TextResponse $response): Provider
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * @param  array<int, ProviderResponse>  $responses
     */
    public function withResponseChain(array $responses): Provider
    {

        $this->responses = $responses;

        return $this;
    }
}
