<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Embeddings;

use Closure;

class Request
{
    /**
     * @param  array<string, mixed>  $clientOptions
     * @param  array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool}  $clientRetry
     */
    public function __construct(
        public readonly string $model,
        public readonly string $input,
        public readonly array $clientOptions,
        public readonly array $clientRetry,
    ) {}
}
