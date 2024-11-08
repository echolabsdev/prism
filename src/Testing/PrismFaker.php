<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Testing;

use Closure;
use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Requests\TextRequest;
use EchoLabs\Prism\ValueObjects\Usage;
use Exception;
use PHPUnit\Framework\Assert as PHPUnit;

class PrismFaker implements Provider
{
    /** @var array<string, array<int, ProviderResponse>> */
    protected array $responses = [];

    /** @var array<string, int> */
    protected array $responseSequence = [];

    /** @var array<string, array<int, TextRequest>> */
    protected array $recorded = [];

    /**
     * @param  array<string, array<int, ProviderResponse>>  $responses
     */
    public function __construct(array $responses = [])
    {
        foreach ($responses as $method => $methodResponses) {
            $this->queueResponses($method, $methodResponses);
        }
    }

    /**
     * Queue responses for a specific method
     *
     * @param  array<int, ProviderResponse>  $responses
     */
    public function queueResponses(string $method, array $responses): self
    {
        $this->responses[$method] = $responses;
        $this->responseSequence[$method] = 0;

        return $this;
    }

    #[\Override]
    public function text(TextRequest $request): ProviderResponse
    {
        $this->recorded['text'][] = $request;

        return $this->nextResponse('text') ?? new ProviderResponse(
            text: '',
            toolCalls: [],
            usage: new Usage(0, 0),
            finishReason: FinishReason::Stop,
            response: ['id' => 'fake', 'model' => 'fake']
        );
    }

    /**
     * @param  Closure(array<int, TextRequest>)  $fn
     */
    public function assertRequest(string $method, Closure $fn): void
    {
        $fn($this->recorded[$method]);
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
     * Assert number of calls made
     */
    public function assertCallCount(int $expectedCount): void
    {
        $actualCount = count($this->recorded['text'] ?? []);
        PHPUnit::assertEquals($expectedCount, $actualCount, "Expected {$expectedCount} calls, got {$actualCount}");
    }
    protected function nextResponse(string $method): ?ProviderResponse
    {
        if (! isset($this->responses[$method])) {
            return null;
        }

        $responses = $this->responses[$method];
        $sequence = $this->responseSequence[$method];

        if (! isset($responses[$sequence])) {
            throw new Exception('Could not find a response for the request');
        }

        $this->responseSequence[$method]++;

        return $responses[$sequence];
    }
}
