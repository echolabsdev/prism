<?php

declare(strict_types=1);

namespace EchoLabs\Prism;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\Provider as ProviderEnum;
use EchoLabs\Prism\Generators\TextGenerator;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Testing\PrismFaker;
use Illuminate\Support\Facades\App;

class Prism
{
    /**
     * @param  array<string, array<int, ProviderResponse>>  $responses
     */
    public static function fake(array $responses = []): PrismFaker
    {
        $faker = new PrismFaker($responses);

        // Replace the PrismManager instance with our faker
        App::instance(PrismManager::class, new class($faker) extends PrismManager
        {
            public function __construct(
                private readonly PrismFaker $faker
            ) {}

            public function resolve(ProviderEnum|string $name): Provider
            {
                return $this->faker;
            }
        });

        return $faker;
    }

    public static function text(): TextGenerator
    {
        return new TextGenerator;
    }
}
