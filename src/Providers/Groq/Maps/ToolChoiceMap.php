<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\Groq\Maps;

use InvalidArgumentException;
use PrismPHP\Prism\Enums\ToolChoice;

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
            null => $toolChoice,
            default => throw new InvalidArgumentException('Invalid tool choice')
        };
    }
}
