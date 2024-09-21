<?php

declare(strict_types=1);

namespace EchoLabs\Prism;

use EchoLabs\Prism\Contracts\Driver;
use EchoLabs\Prism\Generators\TextGenerator;

class Prism
{
    protected Driver $provider;

    public static function text(): TextGenerator
    {
        return new TextGenerator;
    }
}
