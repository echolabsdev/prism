<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

trait HasModel
{
    protected string $model;

    public function usingModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }
}
