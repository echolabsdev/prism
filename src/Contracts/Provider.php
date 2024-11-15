<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Contracts;

use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Text\Request;

interface Provider
{
    public function text(Request $request): ProviderResponse;
}
