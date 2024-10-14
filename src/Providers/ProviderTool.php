<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers;

use EchoLabs\Prism\Tool;

abstract class ProviderTool
{
    /**
     * @return array<string, mixed>
     */
    abstract public static function toArray(Tool $tool): array;

    /**
     * @param  array<int, Tool>  $tools
     * @return array<int, mixed>
     */
    public static function map(array $tools): array
    {
        return array_map(fn (Tool $tool): array => static::toArray($tool), $tools);
    }
}
