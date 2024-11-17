<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use EchoLabs\Prism\Contracts\Schema;
use EchoLabs\Prism\PrismManager;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Text\Generator as TextGenerator;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;

class Generator extends TextGenerator
{
    protected Schema $schema;

    public function structuredRequest(): Request
    {
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
            toolChoice: $this->toolChoice,
            schema: $this->schema,
        );
    }

    public function withSchema(Schema $schema): self
    {
        $this->schema = $schema;

        return $this;
    }
    #[\Override]
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
}
