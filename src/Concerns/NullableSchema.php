<?php

namespace EchoLabs\Prism\Concerns;

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
