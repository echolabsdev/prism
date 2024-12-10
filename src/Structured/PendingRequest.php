<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use EchoLabs\Prism\Concerns\ConfiguresClient;
use EchoLabs\Prism\Concerns\ConfiguresModels;
use EchoLabs\Prism\Concerns\ConfiguresProviders;
use EchoLabs\Prism\Concerns\ConfiguresTools;
use EchoLabs\Prism\Concerns\HasMessages;
use EchoLabs\Prism\Concerns\HasPrompts;
use EchoLabs\Prism\Concerns\HasSchema;
use EchoLabs\Prism\Concerns\HasTools;
use EchoLabs\Prism\Contracts\Schema;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;

class PendingRequest
{
    use ConfiguresClient;
    use ConfiguresModels;
    use ConfiguresProviders;
    use ConfiguresTools;
    use HasMessages;
    use HasPrompts;
    use HasSchema;
    use HasTools;

    public function generate(): Response
    {
        return $this->provider->structured($this->toRequest());
    }

    protected function toRequest(): Request
    {
        if (! $this->schema instanceof Schema) {
            throw new PrismException('No schema present');
        }

        if ($this->messages && $this->prompt) {
            throw PrismException::promptOrMessages();
        }

        if ($this->prompt) {
            $this->messages[] = new UserMessage($this->prompt);
        }

        return new Request(
            model: $this->model,
            systemPrompt: $this->systemPrompt,
            prompt: $this->prompt,
            messages: $this->messages,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            maxSteps: $this->maxSteps,
            topP: $this->topP,
            tools: $this->tools,
            clientOptions: $this->clientOptions,
            clientRetry: $this->clientRetry,
            toolChoice: $this->toolChoice,
            providerMeta: $this->providerMeta,
            schema: $this->schema,
        );
    }
}
