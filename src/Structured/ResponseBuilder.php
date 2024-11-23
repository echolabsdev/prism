<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
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
        /** @var Step */
        $finalStep = $this->steps->last();

        return new Response(
            steps: $this->steps,
            responseMessages: $this->responseMessages,
            text: $finalStep->text,
            object: $finalStep->finishReason === FinishReason::Stop
                ? $this->decodeObject($finalStep->text)
                : [],
            finishReason: $finalStep->finishReason,
            toolCalls: $finalStep->toolCalls,
            toolResults: $finalStep->toolResults,
            usage: $this->calculateTotalUsage(),
            response: $finalStep->response,
        );
    }

    protected function decodeObject(string $responseText): ?array
    {
        if (! json_validate($responseText)) {
            throw PrismException::structuredDecodingError($responseText);
        }

        return json_decode($responseText, true);
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
