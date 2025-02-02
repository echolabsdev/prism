<?php

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Enums\Provider;

trait HasProviderMeta
{
    /** @var array<string, array<string, mixed>> */
    protected array $providerMeta = [];

    /**
     * @param  array<string, mixed>  $meta
     */
    public function withProviderMeta(Provider $provider, array $meta): self
    {
        $this->providerMeta[$provider->value] = $meta;

        return $this;
    }

    /**
     * @return array<string, mixed>> $meta
     */
    public function providerMeta(Provider $provider): array
    {
        return data_get($this->providerMeta, $provider->value, []);
    }
}
