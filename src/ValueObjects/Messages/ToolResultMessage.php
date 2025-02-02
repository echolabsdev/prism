<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\ToolResult;

readonly class ToolResultMessage implements Message
{
    /**
     * @param  ToolResult[]  $toolResults
     */
    public function __construct(
        public array $toolResults
    ) {}
}
