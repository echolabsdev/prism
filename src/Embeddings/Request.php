<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Embeddings;

use Closure;

readonly class Request
{
    /**
     * @param  array<string, mixed>  $clientOptions
     * @param  array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool}  $clientRetry
     */
    public function __construct(
        public string $model,
        public string $input,
        public array $clientOptions,
        public array $clientRetry,
    ) {}
}
