<?php

declare(strict_types=1);

namespace Tests\TestDoubles;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Requests\TextRequest;
use EchoLabs\Prism\ValueObjects\Usage;

class TestProvider implements Provider
{
    public TextRequest $request;

    /** @var array<string, mixed> */
    public array $clientOptions;

    /** @var array<int, ProviderResponse> */
    public array $responses = [];

    public $callCount = 0;

    #[\Override]
    public function text(TextRequest $request): ProviderResponse
    {
        $this->callCount++;

        $this->request = $request;

        return $this->responses[$this->callCount - 1] ?? new ProviderResponse(
            text: "I'm nyx!",
            toolCalls: [],
            usage: new Usage(10, 10),
            finishReason: FinishReason::Stop,
            response: ['id' => '123', 'model' => 'claude-3-5-sonnet-20240620']
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
