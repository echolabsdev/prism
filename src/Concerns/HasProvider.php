<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\Provider as ProviderEnum;

trait HasProvider
{
    protected Provider $provider;

    public function using(ProviderEnum|string $provider, string $model): self
    {
        $this->provider = app('prism-manager')->resolve($provider);
        $this->provider->usingModel($model);

        return $this;
    }
}
