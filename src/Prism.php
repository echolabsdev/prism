<?php

declare(strict_types=1);

namespace EchoLabs\Prism;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Sparkle\ProviderManager;
use Generator;

class Prism
{
    protected Provider $provider;

    public function provider($provider): self
    {
        $this->provider = app(ProviderManager::class)->resolve($provider);

        return $this;
    }

    public function using(string $model): self
    {
        return $this;
    }

    public function withTools(): self
    {
        return $this;
    }

    public function withMessages(): self
    {
        return $this;
    }

    public function withPrompt(string $prompt): self
    {
        return $this;
    }

    public function model(string $model): self
    {
        return $this;
    }

    public function run(): void
    {
        //
    }

    public function stream(): Generator
    {
        yield null;
    }
}
