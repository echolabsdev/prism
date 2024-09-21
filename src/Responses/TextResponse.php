<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Responses;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\States\TextState;
use EchoLabs\Prism\ValueObjects\TextResult;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Support\Collection;

class TextResponse
{
    /** @var Collection<int, TextResult> */
    public readonly Collection $steps;

    /** @var Collection<int, Message> */
    public readonly Collection $responseMessages;

    public readonly Usage $usage;

    public readonly string $text;

    public readonly FinishReason $finishReason;

    /** @var array<int, ToolCall> */
    public readonly array $toolCalls;

    /** @var array<int, ToolResult> */
    public readonly array $toolResults;

    /** @var array{id: string, model: string} */
    public readonly array $response;

    public function __construct(
        protected TextState $state
    ) {
        $this->steps = $state->steps();
        $this->responseMessages = $state->responseMessages();
        $this->usage = $this->calculateTotalUsage();

        /** @var TextResult */
        $finalStep = $this->steps->last();

        $this->text = $finalStep->text;
        $this->finishReason = $finalStep->finishReason;
        $this->toolCalls = $finalStep->toolCalls;
        $this->toolResults = $finalStep->toolResults;
        $this->response = $finalStep->response;
    }

    protected function calculateTotalUsage(): Usage
    {
        return new Usage(
            $this
                ->steps
                ->sum(fn (TextResult $result): int => $result->usage->promptTokens),
            $this
                ->steps
                ->sum(fn (TextResult $result): int => $result->usage->completionTokens)
        );
    }
}
