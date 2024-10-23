<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Contracts;

use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Requests\TextRequest;

interface Provider
{
    public function text(TextRequest $request): ProviderResponse;
}
