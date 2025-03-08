<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Text;

use Closure;
use PrismPHP\Prism\Concerns\ChecksSelf;
use PrismPHP\Prism\Concerns\HasProviderMeta;
use PrismPHP\Prism\Contracts\Message;
use PrismPHP\Prism\Contracts\PrismRequest;
use PrismPHP\Prism\Enums\ToolChoice;
use PrismPHP\Prism\Tool;
use PrismPHP\Prism\ValueObjects\Messages\SystemMessage;

class Request implements PrismRequest
{
    use ChecksSelf, HasProviderMeta;

    /**
     * @param  SystemMessage[]  $systemPrompts
     * @param  array<int, Message>  $messages
     * @param  array<int, Tool>  $tools
     * @param  array<string, mixed>  $clientOptions
     * @param  array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool}  $clientRetry
     * @param  array<string, mixed>  $providerMeta
     */
    public function __construct(
        protected string $model,
        protected array $systemPrompts,
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

    /**
     * @return SystemMessage[]
     */
    public function systemPrompts(): array
    {
        return $this->systemPrompts;
    }

    #[\Override]
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
