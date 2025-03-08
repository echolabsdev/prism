<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Concerns;

trait ConfiguresGeneration
{
    protected int $maxSteps = 1;

    public function withMaxSteps(int $steps): self
    {
        $this->maxSteps = $steps;

        return $this;
    }
}
