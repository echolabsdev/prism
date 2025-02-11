<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Maps;

use EchoLabs\Prism\Enums\ToolChoice;
use InvalidArgumentException;

class ToolChoiceMap
{
    /**
     * @return array<string, mixed>|string|null
     */
    public static function map(string|ToolChoice|null $toolChoice, int $currentStep = 0, int $autoAfter = 1): string|array|null
    {
        if (is_null($toolChoice)) {
            return null;
        }

        if (is_string($toolChoice)) {
            if ($currentStep >= $autoAfter) {
                return [
                    'type' => 'auto',
                ];
            }

            return [
                'type' => 'tool',
                'name' => $toolChoice,
            ];
        }

        if (! in_array($toolChoice, [ToolChoice::Auto, ToolChoice::Any, ToolChoice::None])) {
            throw new InvalidArgumentException('Invalid tool choice');
        }

        return [
            'type' => match ($toolChoice) {
                ToolChoice::Auto => 'auto',
                ToolChoice::Any => $currentStep >= $autoAfter ? 'auto' : 'any',
                ToolChoice::None => 'none',
            },
        ];
    }
}
