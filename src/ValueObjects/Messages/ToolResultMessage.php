<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\ToolResult;

class ToolResultMessage implements Message
{
    /**
     * @param  array<int, ToolResult>  $toolResults
     */
    public function __construct(
        public readonly array $toolResults
    ) {}
}
