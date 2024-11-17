<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Contracts;

use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Text\Request as TextRequest;

interface Provider
{
    public function text(TextRequest $request): ProviderResponse;

    public function structured(StructuredRequest $request): ProviderResponse;
}
