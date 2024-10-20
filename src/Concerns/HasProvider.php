<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Enums\Provider;

trait HasProvider
{
    protected string $provider;

    protected string $model;

    public function using(string|Provider $provider, string $model): self
    {
        $this->provider = is_string($provider) ? $provider : $provider->value;
        $this->model = $model;

        return $this;
    }

    public function provider(): string
    {
        return $this->provider;
    }
}
