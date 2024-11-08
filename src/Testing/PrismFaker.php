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
    protected int $responseSequence = 0;

    /** @var array<int, TextRequest> */
    protected array $recorded = [];

    /**
     * @param  array<int, ProviderResponse>  $responses
     */
    public function __construct(protected array $responses = []) {}

    #[\Override]
    public function text(TextRequest $request): ProviderResponse
    {
        $this->recorded[] = $request;

        return $this->nextResponse() ?? new ProviderResponse(
            text: '',
            toolCalls: [],
            usage: new Usage(0, 0),
            finishReason: FinishReason::Stop,
            response: ['id' => 'fake', 'model' => 'fake']
        );
    }

    /**
     * @param  Closure(array<int, TextRequest>):void  $fn
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
     * Assert number of calls made
     */
    public function assertCallCount(int $expectedCount): void
    {
        $actualCount = count($this->recorded ?? []);

        PHPUnit::assertEquals($expectedCount, $actualCount, "Expected {$expectedCount} calls, got {$actualCount}");
    }

    protected function nextResponse(): ?ProviderResponse
    {
        if (! isset($this->responses)) {
            return null;
        }

        $responses = $this->responses;
        $sequence = $this->responseSequence;

        if (! isset($responses[$sequence])) {
            throw new Exception('Could not find a response for the request');
        }

        $this->responseSequence++;

        return $responses[$sequence];
    }
}
