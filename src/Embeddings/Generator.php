<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Embeddings;

use PrismPHP\Prism\Contracts\Provider;
use PrismPHP\Prism\Exceptions\PrismException;

class Generator
{
    public function __construct(protected Provider $provider) {}

    public function generate(Request $request): Response
    {
        if ($request->inputs() === []) {
            throw new PrismException('Embeddings input is required');
        }

        return $this->provider->embeddings($request);
    }
}
