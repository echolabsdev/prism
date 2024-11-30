<?php

namespace EchoLabs\Prism\Concerns;

trait NullableSchema
{
    /**
     * @return array<int, string>|string
     */
    protected function getNullableType(string $type): array|string
    {
        if ($this->nullable) {
            return [$type, 'null'];
        }

        return $type;
    }
}
