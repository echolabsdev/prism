<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Enums\ToolChoice;
use EchoLabs\Prism\Tool;

trait ConfiguresTools
{
    protected string|ToolChoice|null $toolChoice = null;

    public function withToolChoice(string|ToolChoice|Tool $toolChoice): self
    {
        $this->toolChoice = $toolChoice instanceof Tool
            ? $toolChoice->name()
            : $toolChoice;

        return $this;
    }
}
