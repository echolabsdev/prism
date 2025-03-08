<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Structured;

use PrismPHP\Prism\Concerns\ConfiguresClient;
use PrismPHP\Prism\Concerns\ConfiguresModels;
use PrismPHP\Prism\Concerns\ConfiguresProviders;
use PrismPHP\Prism\Concerns\ConfiguresStructuredOutput;
use PrismPHP\Prism\Concerns\HasMessages;
use PrismPHP\Prism\Concerns\HasPrompts;
use PrismPHP\Prism\Concerns\HasProviderMeta;
use PrismPHP\Prism\Concerns\HasSchema;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\ValueObjects\Messages\UserMessage;

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

        if (! $this->schema instanceof \PrismPHP\Prism\Contracts\Schema) {
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
