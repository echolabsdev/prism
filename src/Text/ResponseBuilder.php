<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Text;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Support\Collection;

class ResponseBuilder
{
    /** @var Collection<int, Step> */
    public readonly Collection $steps;

    /** @var Collection<int, Message> */
    public readonly Collection $responseMessages;

    public function __construct()
    {
        $this->steps = new Collection;
        $this->responseMessages = new Collection;
    }

    public function addResponseMessage(Message $message): self
    {
        $this->responseMessages->push($message);

        return $this;
    }

    public function addStep(Step $step): self
    {
        $this->steps->push($step);

        return $this;
    }

    public function toResponse(): Response
    {
        /** @var Step $finalStep */
        $finalStep = $this->steps->last();

        return new Response(
            steps: $this->steps,
            responseMessages: $this->responseMessages,
            text: $finalStep->text,
            finishReason: $finalStep->finishReason,
            toolCalls: $finalStep->toolCalls,
            toolResults: $finalStep->toolResults,
            usage: $this->calculateTotalUsage(),
            response: $finalStep->response,
            messages: collect($finalStep->messages),
        );
    }

    protected function calculateTotalUsage(): Usage
    {
        return new Usage(
            $this
                ->steps
                ->sum(fn (Step $result): int => $result->usage->promptTokens),
            $this
                ->steps
                ->sum(fn (Step $result): int => $result->usage->completionTokens)
        );
    }
}
