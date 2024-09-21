<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Requests;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Tool;

class TextRequest
{
    /**
     * @param  array<int, Message>  $messages
     * @param  array<int, Tool>  $tools
     */
    public function __construct(
        public readonly ?string $systemPrompt,
        public readonly array $messages,
        public readonly ?int $maxTokens,
        public readonly int|float|null $temperature,
        public readonly int|float|null $topP,
        public readonly array $tools = [],
    ) {}
}
