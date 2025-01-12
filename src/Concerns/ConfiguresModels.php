<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

trait ConfiguresModels
{
    protected ?int $maxTokens = null;

    protected int $maxSteps = 1;

    protected int|float|null $temperature = null;

    protected int|float|null $topP = null;

    public function withMaxTokens(?int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    public function usingTemperature(int|float|null $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function usingTopP(int|float $topP): self
    {
        $this->topP = $topP;

        return $this;
    }

    public function withMaxSteps(int $steps): self
    {
        $this->maxSteps = $steps;

        return $this;
    }
}
