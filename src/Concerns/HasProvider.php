<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Contracts\Provider;

trait HasProvider
{
    protected Provider $provider;

    public function using(Provider $provider): self
    {
        $this->provider = $provider;

        return $this;
    }
}
