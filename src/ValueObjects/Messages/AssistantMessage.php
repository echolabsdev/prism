<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\Messages\Parts\TextPart;
use EchoLabs\Prism\ValueObjects\ToolCall;
use Illuminate\Support\Arr;

class AssistantMessage implements Message
{
    /**
     * @param  string|array<int, TextPart|ToolCall>  $content
     */
    public function __construct(
        public readonly string|array $content = [],
    ) {}

    public function hasToolCall(): bool
    {
        return collect(Arr::wrap($this->content))
            ->contains(fn ($part): bool => $part instanceof ToolCall);
    }

    /**
     * @return array<int, ToolCall>
     */
    public function toolCalls(): array
    {
        return collect(Arr::wrap($this->content))
            ->where(fn ($part): bool => $part instanceof ToolCall)
            ->toArray();
    }

    public function text(): string
    {
        return is_string($this->content)
            ? $this->content
            : collect($this->content)
                ->where(fn ($part): bool => $part instanceof TextPart)
                ->implode(' ');
    }
}
