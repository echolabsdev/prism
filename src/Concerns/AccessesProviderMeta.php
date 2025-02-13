<?php

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Enums\Provider;

trait AccessesProviderMeta
{
    public function providerMeta(string|Provider $provider, string $valuePath = ''): mixed
    {
        $providerMeta = data_get(
            $this->providerMeta,
            is_string($provider) ? $provider : $provider->value,
            []
        );

        return data_get($providerMeta, $valuePath, $providerMeta);
    }
}
