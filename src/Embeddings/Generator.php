<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Embeddings;

use Closure;
use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\Provider as ProviderEnum;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\PrismManager;

class Generator
{
    protected string $input = '';

    /** @var array<string, mixed> */
    protected array $clientOptions = [];

    /** @var array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool} */
    protected array $clientRetry = [0];

    protected Provider $provider;

    protected string $model;

    public function using(string|ProviderEnum $provider, string $model): self
    {
        $this->provider = resolve(PrismManager::class)
            ->resolve($provider);

        $this->model = $model;

        return $this;
    }

    public function fromInput(string $input): self
    {
        $this->input = $input;

        return $this;
    }

    public function fromFile(string $path): self
    {
        if (! is_file($path)) {
            throw new PrismException(sprintf('%s is not a valid file', $path));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new PrismException(sprintf('%s contents could not be read', $path));
        }

        $this->input = $contents;

        return $this;
    }

    public function generate(): Response
    {
        if ($this->input === '' || $this->input === '0') {
            throw new PrismException('Embeddings input is required');
        }

        return $this->provider->embeddings(new Request(
            model: $this->model,
            input: $this->input,
            clientOptions: $this->clientOptions,
            clientRetry: $this->clientRetry,
        ));
    }
}
