<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use EchoLabs\Prism\Concerns\BuildsTextRequests;
use EchoLabs\Prism\Concerns\HandlesToolCalls;
use EchoLabs\Prism\Contracts\Schema;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\PrismManager;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use InvalidArgumentException;

class Generator
{
    use BuildsTextRequests, HandlesToolCalls;

    protected ?Schema $schema = null;

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
            object: $this->decodeObject($response->text),
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

    public function structuredRequest(): Request
    {
        if (is_null($this->schema)) {
            throw new InvalidArgumentException('A Schema must be set using ->withSchema()');
        }

        return new Request(
            model: $this->model,
            systemPrompt: $this->systemPrompt,
            prompt: $this->prompt,
            messages: $this->messages,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            topP: $this->topP,
            tools: $this->tools,
            clientOptions: $this->clientOptions,
            clientRetry: $this->clientRetry,
            toolChoice: $this->toolChoice,
            schema: $this->schema,
            providerMeta: $this->providerMeta,
        );
    }

    public function withSchema(Schema $schema): self
    {
        $this->schema = $schema;

        return $this;
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

    protected function sendProviderRequest(): ProviderResponse
    {
        $response = resolve(PrismManager::class)
            ->resolve($this->provider)
            ->structured($this->structuredRequest());

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
