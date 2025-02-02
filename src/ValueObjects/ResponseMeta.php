<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects;

readonly class ResponseMeta
{
    public function __construct(
        public string $id,
        public string $model
    ) {}
}
