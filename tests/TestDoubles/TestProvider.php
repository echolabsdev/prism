<?php

declare(strict_types=1);

namespace Tests\TestDoubles;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Embeddings\Request as EmbeddingRequest;
use EchoLabs\Prism\Embeddings\Response as EmbeddingResponse;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\ValueObjects\EmbeddingsUsage;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;

class TestProvider implements Provider
{
    public StructuredRequest|TextRequest|EmbeddingRequest $request;

    /** @var array<string, mixed> */
    public array $clientOptions;

    /** @var array<mixed> */
    public array $clientRetry;

    /** @var array<int, ProviderResponse|EmbeddingResponse> */
    public array $responses = [];

    public $callCount = 0;

    #[\Override]
    public function text(TextRequest $request, int $currentStep): ProviderResponse
    {
        $this->callCount++;

        $this->request = $request;

        return $this->responses[$this->callCount - 1] ?? new ProviderResponse(
            text: "I'm nyx!",
            toolCalls: [],
            usage: new Usage(10, 10),
            finishReason: FinishReason::Stop,
            responseMeta: new ResponseMeta('123', 'claude-3-5-sonnet-20240620')
        );
    }

    #[\Override]
    public function structured(StructuredRequest $request): ProviderResponse
    {
        $this->callCount++;

        $this->request = $request;

        return $this->responses[$this->callCount - 1] ?? new ProviderResponse(
            text: json_encode([]),
            toolCalls: [],
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

    public function withResponse(ProviderResponse $response): Provider
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
