<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use Closure;
use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Contracts\Schema;
use EchoLabs\Prism\Enums\ToolChoice;
use EchoLabs\Prism\Tool;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;

class Request
{
    /**
     * @param  array<int, Message>  $messages
     * @param  array<int, Tool>  $tools
     * @param  array<string, mixed>  $clientOptions
     * @param  array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool}  $clientRetry
     */
    public function __construct(
        public readonly ?string $systemPrompt,
        public readonly string $model,
        public readonly ?string $prompt,
        public readonly array $messages,
        public readonly ?int $maxTokens,
        public readonly int|float|null $temperature,
        public readonly int|float|null $topP,
        public readonly array $tools,
        public readonly array $clientOptions,
        public readonly string|ToolChoice|null $toolChoice,
        public readonly array $clientRetry,
        public readonly Schema $schema,
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
            tools: $this->tools,
            clientOptions: $this->clientOptions,
            clientRetry: $this->clientRetry,
            toolChoice: $this->toolChoice,
            schema: $this->schema,
        );
    }
}
