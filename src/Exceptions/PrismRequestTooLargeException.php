<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Exceptions;

use PrismPHP\Prism\Enums\Provider;

class PrismRequestTooLargeException extends PrismException
{
    public function __construct(string|Provider $provider)
    {
        $provider = is_string($provider) ? $provider : $provider->value;

        parent::__construct("Your Prism request to $provider was too large. Consult the provider's documentation.");
    }

    public static function make(string|Provider $provider): self
    {
        return new self($provider);
    }
}
