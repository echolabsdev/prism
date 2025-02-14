<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Text;

use Closure;
use EchoLabs\Prism\Concerns\ChecksSelf;
use EchoLabs\Prism\Concerns\HasProviderMeta;
use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Contracts\PrismRequest;
use EchoLabs\Prism\Enums\ToolChoice;
use EchoLabs\Prism\Tool;

class Request implements PrismRequest
{
    use ChecksSelf, HasProviderMeta;

    /**
     * @param  array<int, Message>  $messages
     * @param  array<int, Tool>  $tools
     * @param  array<string, mixed>  $clientOptions
     * @param  array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool}  $clientRetry
     * @param  array<string, mixed>  $providerMeta
     */
    public function __construct(
        readonly public string $model,
        readonly public ?string $systemPrompt,
        readonly public ?string $prompt,
        readonly public array $messages,
        readonly public int $maxSteps,
        readonly public ?int $maxTokens,
        readonly public int|float|null $temperature,
        readonly public int|float|null $topP,
        readonly public array $tools,
        readonly public array $clientOptions,
        readonly public array $clientRetry,
        readonly public string|ToolChoice|null $toolChoice,
        array $providerMeta = [],
    ) {
        $this->providerMeta = $providerMeta;
    }

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
}
