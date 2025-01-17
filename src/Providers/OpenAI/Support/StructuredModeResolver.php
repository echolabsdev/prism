<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\OpenAI\Support;

use EchoLabs\Prism\Enums\StructuredMode;
use EchoLabs\Prism\Exceptions\PrismException;

class StructuredModeResolver
{
    public static function forModel(string $model): StructuredMode
    {
        if (self::unsupported($model)) {
            throw new PrismException(sprintf('Structured output is not supported for %s', $model));
        }

        if (self::supportsStructuredMode($model)) {
            return StructuredMode::Structured;
        }

        return StructuredMode::Json;
    }

    protected static function supportsStructuredMode(string $model): bool
    {
        return in_array($model, [
            'gpt-4o-mini',
            'gpt-4o-mini-2024-07-18',
            'gpt-4o-2024-08-06',
            'gpt-4o',
            'chatgpt-4o-latest ',
        ]);
    }

    protected static function supportsJsonMode(string $model): bool
    {
        if (preg_match('/^gpt-4-.*/', $model)) {
            return true;
        }

        return $model === 'gpt-3.5-turbo';
    }

    protected static function unsupported(string $model): bool
    {
        return in_array($model, [
            'o1',
            'o1-mini',
            'o1-preview',
        ]);
    }
}
