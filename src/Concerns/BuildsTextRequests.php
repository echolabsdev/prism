<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Text\Request;

trait BuildsTextRequests
{
    protected function textRequest(): Request
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
            clientRetry: $this->clientRetry,
            toolChoice: $this->toolChoice,
            providerMeta: $this->providerMeta,
        );
    }
}
