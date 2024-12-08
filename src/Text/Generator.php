<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Text;

use EchoLabs\Prism\Concerns\BuildsTextRequests;
use EchoLabs\Prism\Concerns\HandlesToolCalls;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\PrismManager;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;

class Generator
{
    use BuildsTextRequests, HandlesToolCalls;

    protected ResponseBuilder $responseBuilder;

    public function __construct()
    {
        $this->responseBuilder = new ResponseBuilder;
    }

    public function generate(): Response
    {
        $response = $this->sendProviderRequest();

        if ($response->finishReason === FinishReason::ToolCalls) {
            $toolResults = $this->handleToolCalls($response);
        }

        $this->responseBuilder->addStep(new Step(
            text: $response->text,
            finishReason: $response->finishReason,
            toolCalls: $response->toolCalls,
            toolResults: $toolResults ?? [],
            usage: $response->usage,
            response: $response->response,
            messages: $this->messages,
        ));

        if ($this->shouldContinue($response)) {
            return $this->generate();
        }

        return $this->responseBuilder->toResponse();
    }

    protected function sendProviderRequest(): ProviderResponse
    {
        $response = resolve(PrismManager::class)
            ->resolve($this->provider, $this->providerConfig)
            ->text($this->textRequest());

        $responseMessage = new AssistantMessage(
            $response->text,
            $response->toolCalls
        );

        $this->responseBuilder->addResponseMessage($responseMessage);
        $this->messages[] = $responseMessage;

        return $response;
    }

    protected function shouldContinue(ProviderResponse $response): bool
    {
        return $this->responseBuilder->steps->count() < $this->maxSteps
            && $response->finishReason !== FinishReason::Stop;
    }
}
