<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects;

class Meta
{
    public function __construct(
        public readonly string $id,
        public readonly string $model,
    ) {}
}
