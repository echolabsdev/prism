<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\OpenAI\Support;

use EchoLabs\Prism\Enums\StructuredMode as StructuredModeEnum;

class StructuredMode
{
    public static function forModel(string $model): StructuredModeEnum
    {
        if (self::supportsStructuredMode($model)) {
            return StructuredModeEnum::Structured;
        }

        if (self::supportsJsonMode($model)) {
            return StructuredModeEnum::Json;
        }

        return StructuredModeEnum::Tool;
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
        if (preg_match('/^gpt-4(?!o)/', $model)) {
            return true;
        }
        return $model === 'gpt-3.5-turbo';
    }
}
