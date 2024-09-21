<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Exceptions;

use Exception;
use Throwable;

class PrismServerException extends Exception
{
    public static function unresolveableModel(string $model, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Prism "%s" is not registered with PrismServer', $model),
            previous: $previous
        );
    }
}
