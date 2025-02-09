<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Contracts;

interface PrismRequest
{
    /**
     * @param  class-string  $classString
     */
    public function is(string $classString): bool;
}
