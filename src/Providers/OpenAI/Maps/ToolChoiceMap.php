<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\OpenAI\Maps;

use EchoLabs\Prism\Enums\ToolChoice;
use InvalidArgumentException;

class ToolChoiceMap
{
    /**
     * @return array<string, mixed>|string|null
     */
    public static function map(string|ToolChoice|null $toolChoice, int $currentStep = 0, int $autoAfter = 1): string|array|null
    {
        if (is_string($toolChoice)) {
            if ($currentStep >= $autoAfter) {
                return 'auto';
            }

            return [
                'type' => 'function',
                'function' => [
                    'name' => $toolChoice,
                ],
            ];
        }

        return match ($toolChoice) {
            ToolChoice::Auto => 'auto',
            ToolChoice::Any => $currentStep >= $autoAfter ? 'auto' : 'required',
            null => $toolChoice,
            default => throw new InvalidArgumentException('Invalid tool choice')
        };
    }
}
