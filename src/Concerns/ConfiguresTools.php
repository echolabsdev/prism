<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Concerns;

use PrismPHP\Prism\Enums\ToolChoice;
use PrismPHP\Prism\Tool;

trait ConfiguresTools
{
    protected string|ToolChoice|null $toolChoice = null;

    protected ?int $toolChoiceAutoAfterSteps = null;

    public function withToolChoice(string|ToolChoice|Tool $toolChoice, ?int $toolChoiceAutoAfterSteps = null): self
    {
        $this->toolChoice = $toolChoice instanceof Tool
            ? $toolChoice->name()
            : $toolChoice;

        $this->toolChoiceAutoAfterSteps = $toolChoiceAutoAfterSteps;

        return $this;
    }
}
