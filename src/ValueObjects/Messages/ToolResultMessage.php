<?php

declare(strict_types=1);

namespace PrismPHP\Prism\ValueObjects\Messages;

use PrismPHP\Prism\Contracts\Message;
use PrismPHP\Prism\ValueObjects\ToolResult;

readonly class ToolResultMessage implements Message
{
    /**
     * @param  ToolResult[]  $toolResults
     */
    public function __construct(
        public array $toolResults
    ) {}
}
