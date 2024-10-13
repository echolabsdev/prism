<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Contracts\Provider;

trait HasProvider
{
    protected Provider $provider;

    public function using(string $provider, string $model): self
    {
        $this->provider = app('prism-manager')->resolve($provider);
        $this->provider->usingModel($model);

        return $this;
    }
}
