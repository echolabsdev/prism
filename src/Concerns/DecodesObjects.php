<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

trait DecodesObjects
{
    /**
     * @return array<mixed>|null
     */
    public function decodeObject(string $responseText): ?array
    {
        if (! json_validate($responseText)) {
            return [];
        }

        return json_decode($responseText, true);
    }
}
