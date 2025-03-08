<?php

namespace PrismPHP\Prism\Concerns;

trait NullableSchema
{
    /**
     * @return array<int, string>
     */
    protected function castToNullable(string $type): array
    {
        return [$type, 'null'];
    }
}
