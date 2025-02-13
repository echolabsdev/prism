<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Embeddings;

use EchoLabs\Prism\Concerns\ConfiguresClient;
use EchoLabs\Prism\Concerns\ConfiguresProviders;
use EchoLabs\Prism\Concerns\HasProviderMeta;
use EchoLabs\Prism\Exceptions\PrismException;

class PendingRequest
{
    use ConfiguresClient;
    use ConfiguresProviders;
    use HasProviderMeta;

    protected string $input = '';

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

    public function generate(): \EchoLabs\Prism\Embeddings\Response
    {
        return (new Generator($this->provider))->generate($this->toRequest());
    }

    protected function toRequest(): Request
    {
        return new Request(
            model: $this->model,
            input: $this->input,
            clientOptions: $this->clientOptions,
            clientRetry: $this->clientRetry,
            providerMeta: $this->providerMeta
        );
    }
}
