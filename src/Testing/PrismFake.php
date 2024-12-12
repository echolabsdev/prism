<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Testing;

use Closure;
use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Embeddings\Request as EmbeddingRequest;
use EchoLabs\Prism\Embeddings\Response as EmbeddingResponse;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Structured\Response as StructuredResponse;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\ValueObjects\EmbeddingsUsage;
use EchoLabs\Prism\ValueObjects\Usage;
use Exception;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;

class PrismFake implements Provider
{
    protected int $responseSequence = 0;

    /** @var array<int, StructuredRequest|TextRequest|EmbeddingRequest> */
    protected array $recorded = [];

    /** @var array<string, mixed> */
    protected $providerConfig = [];

    /**
     * @param  array<int, ProviderResponse|EmbeddingResponse|StructuredResponse>  $responses
     */
    public function __construct(protected array $responses = []) {}

    #[\Override]
    public function text(TextRequest $request): ProviderResponse
    {
        $this->recorded[] = $request;

        /** @var ?ProviderResponse */
        $response = $this->nextResponse();

        return $response ?? new ProviderResponse(
            text: '',
            toolCalls: [],
            usage: new Usage(0, 0),
            finishReason: FinishReason::Stop,
            response: ['id' => 'fake', 'model' => 'fake']
        );
    }

    #[\Override]
    public function embeddings(EmbeddingRequest $request): EmbeddingResponse
    {
        $this->recorded[] = $request;

        return $this->nextEmbeddingResponse() ?? new EmbeddingResponse(
            embeddings: [],
            usage: new EmbeddingsUsage(10),
        );
    }

    #[\Override]
    public function structured(StructuredRequest $request): StructuredResponse
    {
        $this->recorded[] = $request;
        /** @var ?StructuredResponse */
        $response = $this->nextResponse();

        return $response ?? new StructuredResponse(
            steps: new Collection,
            responseMessages: new Collection,
            text: '',
            object: [],
            finishReason: FinishReason::Stop,
            toolCalls: [],
            toolResults: [],
            usage: new Usage(10, 10),
            response: ['id' => '1234', 'model' => 'some-model'],
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function setProviderConfig(array $config): void
    {
        $this->providerConfig = $config;
    }

    /**
     * @param  Closure(array<int, StructuredRequest|TextRequest|EmbeddingRequest>):void  $fn
     */
    public function assertRequest(Closure $fn): void
    {
        $fn($this->recorded);
    }

    public function assertPrompt(string $prompt): void
    {
        $prompts = collect($this->recorded)
            ->flatten()
            ->map
            ->prompt;

        PHPUnit::assertTrue(
            $prompts->contains($prompt),
            "Could not find the prompt {$prompt} in the recorded requests"
        );
    }

    /**
     * @param  array<string, mixed>  $providerConfig
     */
    public function assertProviderConfig(array $providerConfig): void
    {
        PHPUnit::assertEqualsCanonicalizing(
            $providerConfig,
            $this->providerConfig
        );
    }

    /**
     * Assert number of calls made
     */
    public function assertCallCount(int $expectedCount): void
    {
        $actualCount = count($this->recorded ?? []);

        PHPUnit::assertEquals($expectedCount, $actualCount, "Expected {$expectedCount} calls, got {$actualCount}");
    }

    protected function nextResponse(): null|ProviderResponse|StructuredResponse
    {
        if (! isset($this->responses)) {
            return null;
        }

        /** @var array<int, ProviderResponse|StructuredResponse> */
        $responses = $this->responses;
        $sequence = $this->responseSequence;

        if (! isset($responses[$sequence])) {
            throw new Exception('Could not find a response for the request');
        }

        $this->responseSequence++;

        return $responses[$sequence];
    }

    protected function nextEmbeddingResponse(): ?EmbeddingResponse
    {
        if (! isset($this->responses)) {
            return null;
        }

        /** @var EmbeddingResponse[] */
        $responses = $this->responses;
        $sequence = $this->responseSequence;

        if (! isset($responses[$sequence])) {
            throw new Exception('Could not find a response for the request');
        }

        $this->responseSequence++;

        return $responses[$sequence];
    }
}
