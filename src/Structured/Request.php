<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use Closure;
use EchoLabs\Prism\Concerns\ChecksSelf;
use EchoLabs\Prism\Concerns\HasProviderMeta;
use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Contracts\PrismRequest;
use EchoLabs\Prism\Contracts\Schema;
use EchoLabs\Prism\Enums\StructuredMode;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;

class Request implements PrismRequest
{
    use ChecksSelf, HasProviderMeta;

    /**
     * @param  SystemMessage[]  $systemPrompts
     * @param  array<int, Message>  $messages
     * @param  array<string, mixed>  $clientOptions
     * @param  array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool}  $clientRetry
     * @param  array<string, mixed>  $providerMeta
     */
    public function __construct(
        protected array $systemPrompts,
        protected string $model,
        protected ?string $prompt,
        protected array $messages,
        protected ?int $maxTokens,
        protected int|float|null $temperature,
        protected int|float|null $topP,
        protected array $clientOptions,
        protected array $clientRetry,
        protected Schema $schema,
        protected StructuredMode $mode,
        array $providerMeta = [],
    ) {
        $this->providerMeta = $providerMeta;
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

    public function prompt(): ?string
    {
        return $this->prompt;
    }

    /**
     * @return array<int, Message>
     */
    public function messages(): array
    {
        return $this->messages;
    }

    public function maxTokens(): ?int
    {
        return $this->maxTokens;
    }

    public function temperature(): int|float|null
    {
        return $this->temperature;
    }

    public function topP(): int|float|null
    {
        return $this->topP;
    }

    /**
     * @return array<string, mixed>
     */
    public function clientOptions(): array
    {
        return $this->clientOptions;
    }

    /**
     * @return array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool}
     */
    public function clientRetry(): array
    {
        return $this->clientRetry;
    }

    public function schema(): Schema
    {
        return $this->schema;
    }

    public function mode(): StructuredMode
    {
        return $this->mode;
    }

    public function addMessage(UserMessage|SystemMessage $message): self
    {
        $this->messages = array_merge($this->messages, [$message]);

        return $this;
    }
}
