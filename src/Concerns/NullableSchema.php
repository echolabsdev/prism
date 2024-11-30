<?php

namespace EchoLabs\Prism\Concerns;

trait NullableSchema
{
    protected function getNullableType(string $type): array|string
    {
        if ($this->nullable) {
            return [$type, 'null'];
        }

        return $type;
    }
}