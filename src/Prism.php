<?php

declare(strict_types=1);

namespace EchoLabs\Prism;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\Provider as ProviderEnum;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Structured\Generator as StructuredGenerator;
use EchoLabs\Prism\Testing\PrismFake;
use EchoLabs\Prism\Text\Generator as TextGenerator;

class Prism
{
    /**
     * @param  array<int, ProviderResponse>  $responses
     */
    public static function fake(array $responses = []): PrismFake
    {
        $fake = new PrismFake($responses);

        app()->instance(PrismManager::class, new class($fake) extends PrismManager
        {
            public function __construct(
                private readonly PrismFake $fake
            ) {}

            public function resolve(ProviderEnum|string $name): Provider
            {
                return $this->fake;
            }
        });

        return $fake;
    }

    public static function text(): TextGenerator
    {
        return new TextGenerator;
    }

    public static function structured(): StructuredGenerator
    {
        return new StructuredGenerator;
    }
}
