<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Contracts\Driver;

trait HasDriver
{
    protected Driver $driver;

    public function using(string $provider, string $model): self
    {
        $this->driver = app('prism-manager')->resolve($provider);
        $this->driver->usingModel($model);

        return $this;
    }
}
