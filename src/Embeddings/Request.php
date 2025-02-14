<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Embeddings;

use Closure;
use EchoLabs\Prism\Concerns\ChecksSelf;
use EchoLabs\Prism\Concerns\HasProviderMeta;
use EchoLabs\Prism\Contracts\PrismRequest;

class Request implements PrismRequest
{
    use ChecksSelf, HasProviderMeta;

    /**
     * @param  array<string, mixed>  $clientOptions
     * @param  array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool}  $clientRetry
     * @param  array<string, mixed>  $providerMeta
     */
    public function __construct(
        readonly public string $model,
        readonly public string $input,
        readonly public array $clientOptions,
        readonly public array $clientRetry,
        array $providerMeta = [],
    ) {
        $this->providerMeta = $providerMeta;
    }
}
