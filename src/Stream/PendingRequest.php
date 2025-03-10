<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Stream;

use Generator;
use PrismPHP\Prism\Concerns\ConfiguresClient;
use PrismPHP\Prism\Concerns\ConfiguresGeneration;
use PrismPHP\Prism\Concerns\ConfiguresModels;
use PrismPHP\Prism\Concerns\ConfiguresProviders;
use PrismPHP\Prism\Concerns\ConfiguresTools;
use PrismPHP\Prism\Concerns\HasMessages;
use PrismPHP\Prism\Concerns\HasPrompts;
use PrismPHP\Prism\Concerns\HasProviderMeta;
use PrismPHP\Prism\Concerns\HasTools;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\ValueObjects\Messages\UserMessage;

class PendingRequest
{
    use ConfiguresClient;
    use ConfiguresGeneration;
    use ConfiguresModels;
    use ConfiguresProviders;
    use ConfiguresTools;
    use HasMessages;
    use HasPrompts;
    use HasProviderMeta;
    use HasTools;

    public function generate(): Generator
    {
        return $this->provider->stream($this->toRequest());
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

        return new Request(
            model: $this->model,
            systemPrompts: $this->systemPrompts,
            prompt: $this->prompt,
            messages: $messages,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            maxSteps: $this->maxSteps,
            topP: $this->topP,
            tools: $this->tools,
            clientOptions: $this->clientOptions,
            clientRetry: $this->clientRetry,
            toolChoice: $this->toolChoice,
            toolChoiceAutoAfterSteps: $this->toolChoiceAutoAfterSteps,
            providerMeta: $this->providerMeta,
        );
    }
}
