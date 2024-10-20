<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Contracts;

use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Requests\TextRequest;

interface Provider
{
    public function usingModel(string $model): Provider;

    public function text(TextRequest $request): ProviderResponse;

    /**
     * @param  array<string, mixed>  $options
     */
    public function withClientOptions(array $options): Provider;
}
