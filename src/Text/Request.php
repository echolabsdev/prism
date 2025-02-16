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
        protected string $model,
        protected ?string $systemPrompt,
        protected ?string $prompt,
        protected array $messages,
        protected int $maxSteps,
        protected ?int $maxTokens,
        protected int|float|null $temperature,
        protected int|float|null $topP,
        protected array $tools,
        protected array $clientOptions,
        protected array $clientRetry,
        protected string|ToolChoice|null $toolChoice,
        array $providerMeta = [],
    ) {
        $this->providerMeta = $providerMeta;
    }

    public function toolChoice(): string|ToolChoice|null
    {
        return $this->toolChoice;
    }

    /**
     * @return array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool} $clientRetry
     */
    public function clientRetry(): array
    {
        return $this->clientRetry;
    }

    /**
     * @return array<string, mixed> $clientOptions
     */
    public function clientOptions(): array
    {
        return $this->clientOptions;
    }

    /**
     * @return Tool[]
     */
    public function tools(): array
    {
        return $this->tools;
    }

    public function topP(): int|float|null
    {
        return $this->topP;
    }

    public function temperature(): int|float|null
    {
        return $this->temperature;
    }

    public function maxTokens(): ?int
    {
        return $this->maxTokens;
    }

    public function maxSteps(): int
    {
        return $this->maxSteps;
    }

    /**
     * @return Message[]
     */
    public function messages(): array
    {
        return $this->messages;
    }

    public function prompt(): ?string
    {
        return $this->prompt;
    }

    public function systemPrompt(): ?string
    {
        return $this->systemPrompt;
    }

    public function model(): string
    {
        return $this->model;
    }

    public function addMessage(Message $message): self
    {
        $this->messages = array_merge($this->messages, [$message]);

        return $this;
    }
}
