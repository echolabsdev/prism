<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\Provider as ProviderEnum;
use EchoLabs\Prism\PrismManager;

trait ConfiguresProviders
{
    protected Provider $provider;

    protected string $model;

    /** @var array<string, mixed> */
    protected array $providerConfig = [];

    /** @var array<string, array<string, mixed>> */
    protected $providerMeta = [];

    public function using(string|ProviderEnum $provider, string $model): self
    {
        $key = is_string($provider) ? $provider : $provider->value;

        $this->provider = resolve(PrismManager::class)->resolve($key, $this->providerConfig);

        $this->model = $model;

        return $this;
    }

    public function provider(): Provider
    {
        return $this->provider;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function withProviderMeta(string|ProviderEnum $provider, array $meta): self
    {
        $key = is_string($provider) ? $provider : $provider->value;

        $this->providerMeta[$key] = $meta;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function usingProviderConfig(array $config): self
    {
        $this->providerConfig = $config;

        return $this;
    }
}
