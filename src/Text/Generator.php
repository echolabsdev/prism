<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Text;

use EchoLabs\Prism\Concerns\BuildsTextRequests;
use EchoLabs\Prism\Concerns\HandlesToolCalls;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\PrismManager;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;

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
            ->resolve($this->provider)
            ->text($this->textRequest());

        $responseMessage = new AssistantMessage(
            $response->text,
            $response->toolCalls
        );

        $this->responseBuilder->addResponseMessage($responseMessage);
        $this->messages[] = $responseMessage;

        return $response;
    }

    /**
     * @return array<int, ToolResult>
     */
    protected function handleToolCalls(ProviderResponse $response): array
    {
        $toolResults = array_map(function (ToolCall $toolCall): ToolResult {
            $result = $this->handleToolCall($this->tools, $toolCall);

            return new ToolResult(
                toolCallId: $toolCall->id,
                toolName: $toolCall->name,
                args: $toolCall->arguments(),
                result: $result,
            );
        }, $response->toolCalls);

        $resultMessage = new ToolResultMessage($toolResults);

        $this->messages[] = $resultMessage;
        $this->responseBuilder->addResponseMessage($resultMessage);

        return $toolResults;
    }

    protected function shouldContinue(ProviderResponse $response): bool
    {
        return $this->responseBuilder->steps->count() < $this->maxSteps
            && $response->finishReason !== FinishReason::Stop;
    }
}
