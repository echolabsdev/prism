<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages;

use EchoLabs\Prism\Concerns\HasProviderMeta;
use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\ToolCall;

class AssistantMessage implements Message
{
    use HasProviderMeta;

    /**
     * @param  ToolCall[]  $toolCalls
     */
    public function __construct(
        public readonly string $content,
        public readonly array $toolCalls = []
    ) {}
}
