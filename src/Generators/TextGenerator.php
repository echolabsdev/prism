<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Generators;

use EchoLabs\Prism\Concerns\BuildsTextRequests;
use EchoLabs\Prism\Concerns\HandlesToolCalls;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\PrismManager;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Responses\TextResponse;
use EchoLabs\Prism\States\TextState;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\TextStep;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;

class TextGenerator
{
    use BuildsTextRequests, HandlesToolCalls;

    protected TextState $state;

    public function __construct()
    {
        $this->state = new TextState;
    }

    public function generate(): TextResponse
    {
        $response = $this->sendProviderRequest();

        if ($response->finishReason === FinishReason::ToolCalls) {
            $toolResults = $this->handleToolCalls($response);
        }

        $this->state->addStep(
            $this->resultFromResponse($response, $toolResults ?? [])
        );

        if ($this->shouldContinue($response)) {
            return $this->generate();
        }

        return new TextResponse($this->state);
    }

    /**
     * @param  array<int, ToolResult>  $toolResults
     */
    protected function resultFromResponse(ProviderResponse $response, array $toolResults): TextStep
    {
        return new TextStep(
            text: $response->text,
            finishReason: $response->finishReason,
            toolCalls: $response->toolCalls,
            toolResults: $toolResults,
            usage: $response->usage,
            response: $response->response,
            messages: $this->state->messages()->toArray(),
        );
    }

    protected function sendProviderRequest(): ProviderResponse
    {
        $response = resolve(PrismManager::class)
            ->resolve($this->provider)
            ->text($this->textRequest());

        $this->state->addResponseMessage(
            new AssistantMessage(
                $response->text,
                $response->toolCalls
            )
        );

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

        $this->state->addResponseMessage(new ToolResultMessage($toolResults));

        return $toolResults;
    }

    protected function shouldContinue(ProviderResponse $response): bool
    {
        return $this->state->steps()->count() < $this->maxSteps
            && $response->finishReason !== FinishReason::Stop;
    }
}
