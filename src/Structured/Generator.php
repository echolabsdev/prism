<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\ProviderResponse;

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
        $this->messages = $request->messages();

        $response = $this->sendProviderRequest($request);

        $this->responseBuilder->addStep(new Step(
            text: $response->text,
            object: $this->decodeObject($response->text),
            finishReason: $response->finishReason,
            usage: $response->usage,
            responseMeta: $response->responseMeta,
            messages: $this->messages,
            additionalContent: $response->additionalContent,
        ));

        return $this->responseBuilder->toResponse();
    }

    /**
     * @return array<mixed>|null
     */
    protected function decodeObject(string $responseText): ?array
    {
        return json_decode($responseText, true);
    }

    protected function sendProviderRequest(Request $request): ProviderResponse
    {
        $response = $this->provider->structured($request);

        if (! $response instanceof ProviderResponse) {
            throw new PrismException('Provider response must be an instance of ProviderResponse');
        }

        $responseMessage = new AssistantMessage(
            $response->text,
            $response->toolCalls,
            $response->additionalContent
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
