<?php

declare(strict_types=1);

namespace EchoLabs\Prism;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Generators\TextGenerator;

class Prism
{
    protected Provider $provider;

    public static function text(): TextGenerator
    {
        return new TextGenerator;
    }
}
