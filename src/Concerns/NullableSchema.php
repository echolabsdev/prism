<?php

namespace EchoLabs\Prism\Concerns;

trait NullableSchema
{
    /**
     * @return array<int, string>|string
     */
    protected function castToNullable(string $type): array|string
    {
        return [$type, 'null'];
    }
}
