<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Embeddings;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Exceptions\PrismException;

class Generator
{
    public function __construct(protected Provider $provider) {}

    public function generate(Request $request): Response
    {
        if ($request->input === '' || $request->input === '0') {
            throw new PrismException('Embeddings input is required');
        }

        return $this->provider->embeddings($request);
    }
}
