<?php

declare(strict_types=1);

namespace PrismPHP\Prism\ValueObjects\Messages;

use PrismPHP\Prism\Concerns\HasProviderMeta;
use PrismPHP\Prism\Contracts\Message;
use PrismPHP\Prism\ValueObjects\ToolCall;

class AssistantMessage implements Message
{
    use HasProviderMeta;

    /**
     * @param  ToolCall[]  $toolCalls
     * @param  array<string,mixed>  $additionalContent
     */
    public function __construct(
        public readonly string $content,
        public readonly array $toolCalls = [],
        public readonly array $additionalContent = []
    ) {}
}
