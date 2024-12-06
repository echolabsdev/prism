<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\XAI\Maps;

use EchoLabs\Prism\Enums\ToolChoice;
use InvalidArgumentException;

class ToolChoiceMap
{
    /**
     * @return array<string, mixed>|string|null
     */
    public static function map(string|ToolChoice|null $toolChoice): string|array|null
    {
        if (is_string($toolChoice)) {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $toolChoice,
                ],
            ];
        }

        return match ($toolChoice) {
            ToolChoice::Auto => 'auto',
            ToolChoice::Any => 'required',
            null => $toolChoice,
            default => throw new InvalidArgumentException('Invalid tool choice')
        };
    }
}
