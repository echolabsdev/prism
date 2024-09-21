<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\ToolCall;

class AssistantMessage implements Message
{
    /**
     * @param  array<int, ToolCall>  $toolCalls
     */
    public function __construct(
        protected readonly string $content = '',
        protected array $toolCalls = []
    ) {}

    #[\Override]
    public function content(): string
    {
        return $this->content;
    }

    public function hasToolCall(): bool
    {
        return $this->toolCalls !== [];
    }

    /**
     * @return array<int, ToolCall> $toolCalls
     */
    public function toolCalls(): array
    {
        return $this->toolCalls;
    }
}
