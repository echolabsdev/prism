<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Embeddings;

use Closure;
use EchoLabs\Prism\Concerns\AccessesProviderMeta;
use EchoLabs\Prism\Concerns\ChecksSelf;
use EchoLabs\Prism\Contracts\PrismRequest;

readonly class Request implements PrismRequest
{
    use AccessesProviderMeta, ChecksSelf;

    /**
     * @param  array<string, mixed>  $clientOptions
     * @param  array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool}  $clientRetry
     * @param  array<string, mixed>  $providerMeta
     */
    public function __construct(
        public string $model,
        public string $input,
        public array $clientOptions,
        public array $clientRetry,
        public array $providerMeta
    ) {}
}
