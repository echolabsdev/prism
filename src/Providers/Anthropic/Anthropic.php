<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic;

use EchoLabs\Prism\Contracts\Provider;

class Anthropic implements Provider
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $apiVersion,
    ) {}
}
