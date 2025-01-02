<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Exceptions;

use EchoLabs\Prism\ValueObjects\ToolCall;
use Exception;
use Throwable;

class PrismException extends Exception
{
    public static function promptOrMessages(): self
    {
        return new self('You can only use `prompt` or `messages`');
    }

    public static function toolNotFound(ToolCall $toolCall, Throwable $previous): self
    {
        return new self(
            sprintf('Tool (%s) not found', $toolCall->name),
            previous: $previous
        );
    }

    public static function multipleToolsFound(ToolCall $toolCall, Throwable $previous): self
    {
        return new self(
            sprintf('Multiple tools with the name %s found', $toolCall->name),
            previous: $previous
        );
    }

    public static function toolCallFailed(ToolCall $toolCall, Throwable $previous): self
    {
        return new self(
            sprintf('Calling %s tool failed', $toolCall->name),
            previous: $previous
        );
    }

    public static function invalidParameterInTool(string $toolName, Throwable $previous): self
    {
        return new self(
            sprintf('Invalid parameters for tool : %s', $toolName),
            previous: $previous
        );
    }

    public static function providerResponseError(string $message): self
    {
        return new self($message);
    }

    public static function providerRequestError(string $model, Throwable $previous): self
    {
        return new self(vsprintf('Sending to model (%s) failed: %s', [
            $model,
            $previous->getMessage(),
        ]), previous: $previous);
    }

    public static function structuredDecodingError(string $responseText): self
    {
        return new self(sprintf(
            'Structured object could not be decoded. Received: %s',
            $responseText
        ));
    }
}
