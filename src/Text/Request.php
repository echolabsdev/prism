<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Text;

use Closure;
use EchoLabs\Prism\Concerns\ChecksSelf;
use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Contracts\PrismRequest;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Enums\ToolChoice;
use EchoLabs\Prism\Tool;

readonly class Request implements PrismRequest
{
    use ChecksSelf;

    /**
     * @param  array<int, Message>  $messages
     * @param  array<int, Tool>  $tools
     * @param  array<string, mixed>  $clientOptions
     * @param  array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool}  $clientRetry
     * @param  array<string, mixed>  $providerMeta
     */
    public function __construct(
        public string $model,
        public ?string $systemPrompt,
        public ?string $prompt,
        public array $messages,
        public int $maxSteps,
        public ?int $maxTokens,
        public int|float|null $temperature,
        public int|float|null $topP,
        public array $tools,
        public array $clientOptions,
        public array $clientRetry,
        public string|ToolChoice|null $toolChoice,
        public array $providerMeta,
    ) {}

    public function addMessage(Message $message): self
    {
        $messages = array_merge($this->messages, [$message]);

        return new self(
            systemPrompt: $this->systemPrompt,
            model: $this->model,
            prompt: $this->prompt,
            messages: $messages,
            maxSteps: $this->maxSteps,
            maxTokens: $this->maxTokens,
            temperature: $this->temperature,
            topP: $this->topP,
            tools: $this->tools,
            clientOptions: $this->clientOptions,
            clientRetry: $this->clientRetry,
            toolChoice: $this->toolChoice,
            providerMeta: $this->providerMeta,
        );
    }

    public function providerMeta(string|Provider $provider, string $valuePath = ''): mixed
    {
        $providerMeta = data_get(
            $this->providerMeta,
            is_string($provider) ? $provider : $provider->value,
            []
        );

        return data_get($providerMeta, $valuePath, $providerMeta);
    }
}
