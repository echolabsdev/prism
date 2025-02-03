<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use Closure;
use EchoLabs\Prism\Concerns\ChecksSelf;
use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Contracts\PrismRequest;
use EchoLabs\Prism\Contracts\Schema;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Enums\StructuredMode;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;

readonly class Request implements PrismRequest
{
    use ChecksSelf;

    /**
     * @param  array<int, Message>  $messages
     * @param  array<string, mixed>  $clientOptions
     * @param  array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool}  $clientRetry
     * @param  array<string, mixed>  $providerMeta
     */
    public function __construct(
        public readonly ?string $systemPrompt,
        public readonly string $model,
        public readonly ?string $prompt,
        public readonly array $messages,
        public readonly ?int $maxTokens,
        public readonly int|float|null $temperature,
        public readonly int|float|null $topP,
        public readonly array $clientOptions,
        public readonly array $clientRetry,
        public readonly Schema $schema,
        public readonly array $providerMeta,
        public readonly StructuredMode $mode,
    ) {}

    public function addMessage(UserMessage|SystemMessage $message): self
    {
        $messages = array_merge($this->messages, [$message]);

        return new self(
            systemPrompt: $this->systemPrompt,
            model: $this->model,
            prompt: $this->prompt,
            messages: $messages,
            maxTokens: $this->maxTokens,
            temperature: $this->temperature,
            topP: $this->topP,
            clientOptions: $this->clientOptions,
            clientRetry: $this->clientRetry,
            schema: $this->schema,
            providerMeta: $this->providerMeta,
            mode: $this->mode,
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
