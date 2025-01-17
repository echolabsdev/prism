<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Text;

use EchoLabs\Prism\Concerns\CallsTools;
use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;

class Generator
{
    use CallsTools;

    /** @var Message[] */
    protected array $messages = [];

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
            $response->toolCalls
        );

        $this->responseBuilder->addResponseMessage($responseMessage);
        $this->messages[] = $responseMessage;

        $request = $request->addMessage($responseMessage);

        if ($response->finishReason === FinishReason::ToolCalls) {
            $toolResults = $this->callTools($request->tools, $response->toolCalls);
            $message = new ToolResultMessage($toolResults);
            $this->messages[] = $message;

            $request = $request->addMessage($message);
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

        if ($this->shouldContinue($request->maxSteps, $response)) {
            return $this->generate($request);
        }

        return $this->responseBuilder->toResponse();
    }

    protected function sendProviderRequest(Request $request): ProviderResponse
    {
        return $this->provider->text($request);
    }

    protected function shouldContinue(int $maxSteps, ProviderResponse $response): bool
    {
        return $this->responseBuilder->steps->count() < $maxSteps
            && $response->finishReason !== FinishReason::Stop;
    }
}
