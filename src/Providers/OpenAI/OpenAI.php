<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\OpenAI;

use EchoLabs\Prism\Contracts\Provider;

class OpenAI implements Provider
{
    public function __construct(
        protected readonly string $apiKey,
        protected readonly string $url
    ) {}
}
