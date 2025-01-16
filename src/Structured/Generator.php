<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;

class Generator
{
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

        $this->responseBuilder->addStep(new Step(
            text: $response->text,
            object: $this->decodeObject($response->text),
            finishReason: $response->finishReason,
            usage: $response->usage,
            response: $response->response,
            messages: $this->messages,
        ));

        return $this->responseBuilder->toResponse();
    }

    /**
     * @return array<mixed>
     */
    protected function decodeObject(string $responseText): ?array
    {
        if (! json_validate($responseText)) {
            return null;
        }

        return json_decode($responseText, true);
    }

    protected function sendProviderRequest(Request $request): ProviderResponse
    {
        $response = $this->provider->structured($request);

        $responseMessage = new AssistantMessage(
            $response->text,
            $response->toolCalls
        );

        $this->responseBuilder->addResponseMessage($responseMessage);
        $this->messages[] = $responseMessage;

        return $response;
    }

    protected function shouldContinue(int $maxSteps, ProviderResponse $response): bool
    {
        return $this->responseBuilder->steps->count() < $maxSteps
            && $response->finishReason !== FinishReason::Stop;
    }
}
