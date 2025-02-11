<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Gemini\Maps;

use EchoLabs\Prism\Enums\ToolChoice;

class ToolChoiceMap
{
    /**
     * @return array<string, mixed>|string|null
     */
    public static function map(string|ToolChoice|null $toolChoice, int $currentStep = 0, int $autoAfter = 1): string|array|null
    {
        if (is_string($toolChoice)) {
            if ($currentStep >= $autoAfter) {
                return ['function_calling_config' => ['mode' => 'AUTO']];
            }

            return [
                'function_calling_config' => [
                    'mode' => 'ANY',
                    'allowed_function_names' => [$toolChoice],
                ],
            ];
        }

        return match ($toolChoice) {
            ToolChoice::Any => ['function_calling_config' => ['mode' => $currentStep >= $autoAfter ? 'AUTO' : 'ANY']],
            ToolChoice::Auto => ['function_calling_config' => ['mode' => 'AUTO']],
            ToolChoice::None => ['function_calling_config' => ['mode' => 'NONE']],
            null => $toolChoice,
        };
    }
}
