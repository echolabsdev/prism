<?php

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Enums\Provider;

trait HasProviderMeta
{
    /** @var array<string, array<string, mixed>> */
    protected $providerMeta = [];

    /**
     * @param  array<string, mixed>  $meta
     */
    public function withProviderMeta(string|Provider $provider, array $meta): self
    {
        $this->providerMeta[is_string($provider) ? $provider : $provider->value] = $meta;

        return $this;
    }

    /**
     * @return array<string, mixed>> $meta
     */
    public function providerMeta(string|Provider $provider): array
    {
        return data_get(
            $this->providerMeta,
            is_string($provider) ? $provider : $provider->value,
            []
        );
    }
}
