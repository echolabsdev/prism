<?php

declare(strict_types=1);

namespace PrismPHP\Prism\ValueObjects;

readonly class Meta
{
    /**
     * @param  ProviderRateLimit[]  $rateLimits
     */
    public function __construct(
        public string $id,
        public string $model,
        public array $rateLimits = [],
    ) {}
}
