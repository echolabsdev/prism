<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Embeddings;

use PrismPHP\Prism\Concerns\ConfiguresClient;
use PrismPHP\Prism\Concerns\ConfiguresProviders;
use PrismPHP\Prism\Concerns\HasProviderMeta;
use PrismPHP\Prism\Exceptions\PrismException;

class PendingRequest
{
    use ConfiguresClient;
    use ConfiguresProviders;
    use HasProviderMeta;

    /** @var array<string> */
    protected array $inputs = [];

    public function fromInput(string $input): self
    {
        $this->inputs[] = $input;

        return $this;
    }

    /**
     * @param  array<string>  $inputs
     */
    public function fromArray(array $inputs): self
    {
        $this->inputs = array_merge($this->inputs, $inputs);

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

        $this->inputs[] = $contents;

        return $this;
    }

    public function generate(): \PrismPHP\Prism\Embeddings\Response
    {
        return (new Generator($this->provider))->generate($this->toRequest());
    }

    protected function toRequest(): Request
    {
        return new Request(
            model: $this->model,
            inputs: $this->inputs,
            clientOptions: $this->clientOptions,
            clientRetry: $this->clientRetry,
            providerMeta: $this->providerMeta
        );
    }
}
