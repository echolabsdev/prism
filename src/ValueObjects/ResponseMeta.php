<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects;

class ResponseMeta
{
    /**
     * @param  ProviderRateLimit[]  $rateLimits
     */
    public function __construct(
        public readonly string $id,
        public readonly string $model,
        public readonly array $rateLimits = []
    ) {}
}
