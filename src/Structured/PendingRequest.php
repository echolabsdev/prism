<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use EchoLabs\Prism\Concerns\ConfiguresClient;
use EchoLabs\Prism\Concerns\ConfiguresModels;
use EchoLabs\Prism\Concerns\ConfiguresProviders;
use EchoLabs\Prism\Concerns\ConfiguresStructuredOutput;
use EchoLabs\Prism\Concerns\HasMessages;
use EchoLabs\Prism\Concerns\HasPrompts;
use EchoLabs\Prism\Concerns\HasProviderMeta;
use EchoLabs\Prism\Concerns\HasSchema;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;

class PendingRequest
{
    use ConfiguresClient;
    use ConfiguresModels;
    use ConfiguresProviders;
    use ConfiguresStructuredOutput;
    use HasMessages;
    use HasPrompts;
    use HasProviderMeta;
    use HasSchema;

    public function generate(): Response
    {
        return $this->provider->structured($this->toRequest());
    }

    public function toRequest(): Request
    {
        if ($this->messages && $this->prompt) {
            throw PrismException::promptOrMessages();
        }

        $messages = $this->messages;

        if ($this->prompt) {
            $messages[] = new UserMessage($this->prompt);
        }

        if (! $this->schema instanceof \EchoLabs\Prism\Contracts\Schema) {
            throw new PrismException('A schema is required for structured output');
        }

        return new Request(
            model: $this->model,
            systemPrompts: $this->systemPrompts,
            prompt: $this->prompt,
            messages: $messages,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            topP: $this->topP,
            clientOptions: $this->clientOptions,
            clientRetry: $this->clientRetry,
            providerMeta: $this->providerMeta,
            schema: $this->schema,
            mode: $this->structuredMode,
        );
    }
}
