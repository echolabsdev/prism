<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Text;

use EchoLabs\Prism\Concerns\CallsTools;
use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\ProviderResponse;

class Generator
{
    use CallsTools;

    protected ResponseBuilder $responseBuilder;

    public function __construct(protected Provider $provider)
    {
        $this->responseBuilder = new ResponseBuilder;
    }

    public function generate(Request $request): Response
    {
        $response = $this->sendProviderRequest($request);

        $responseMessage = new AssistantMessage(
            $response->text,
            $response->toolCalls,
            $response->additionalContent,
        );

        $this->responseBuilder->addResponseMessage($responseMessage);

        $request = $request->addMessage($responseMessage);

        if ($response->finishReason === FinishReason::ToolCalls) {
            $toolResults = $this->callTools($request->tools(), $response->toolCalls);
            $message = new ToolResultMessage($toolResults);

            $request = $request->addMessage($message);
        }

        $this->responseBuilder->addStep(new Step(
            text: $response->text,
            finishReason: $response->finishReason,
            toolCalls: $response->toolCalls,
            toolResults: $toolResults ?? [],
            usage: $response->usage,
            responseMeta: $response->responseMeta,
            messages: $request->messages(),
            additionalContent: $response->additionalContent,
        ));

        if ($this->shouldContinue($request->maxSteps(), $response)) {
            return $this->generate($request);
        }

        return $this->responseBuilder->toResponse();
    }

    protected function sendProviderRequest(Request $request): ProviderResponse
    {
        $response = $this->provider->text($request);

        if (! $response instanceof ProviderResponse) {
            throw new PrismException('Provider response must be an instance of ProviderResponse');
        }

        return $response;
    }

    protected function shouldContinue(int $maxSteps, ProviderResponse $response): bool
    {
        return $this->responseBuilder->steps->count() < $maxSteps
            && $response->finishReason !== FinishReason::Stop;
    }
}
