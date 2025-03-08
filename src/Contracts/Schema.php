<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Contracts;

interface Schema
{
    public function name(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
