<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Contracts;

interface Message
{
    public function content(): string;
}
