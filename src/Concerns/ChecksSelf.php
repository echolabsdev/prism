<?php

namespace EchoLabs\Prism\Concerns;

trait ChecksSelf
{
    /**
     * @param  class-string  $classString
     */
    public function is(string $classString): bool
    {
        return $this instanceof $classString;
    }
}
