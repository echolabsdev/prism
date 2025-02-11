<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Enums\ToolChoice;
use EchoLabs\Prism\Tool;

trait ConfiguresTools
{
    protected string|ToolChoice|null $toolChoice = null;
    protected int $toolChoiceAutoAfter = 1;

    public function withToolChoice(string|ToolChoice|Tool $toolChoice, int $toolChoiceAutoAfter = 1): self
    {
        $this->toolChoice = $toolChoice instanceof Tool
            ? $toolChoice->name()
            : $toolChoice;

        $this->toolChoiceAutoAfter = $toolChoiceAutoAfter;

        return $this;
    }
}
