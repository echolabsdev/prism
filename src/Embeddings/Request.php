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
        protected string $model,
        protected string $input,
        protected array $clientOptions,
        protected array $clientRetry,
        array $providerMeta = [],
    ) {
        $this->providerMeta = $providerMeta;
    }

    /**
     * @return array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool} $clientRetry
     */
    public function clientRetry(): array
    {
        return $this->clientRetry;
    }

    /**
     * @return array<string, mixed> $clientOptions
     */
    public function clientOptions(): array
    {
        return $this->clientOptions;
    }

    public function input(): string
    {
        return $this->input;
    }

    public function model(): string
    {
        return $this->model;
    }
}
