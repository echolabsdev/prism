<?php

declare(strict_types=1);

namespace Tests\Http;

use EchoLabs\Prism\Facades\PrismServer;
use EchoLabs\Prism\Generators\TextGenerator;
use Illuminate\Testing\TestResponse;

it('it returns prisms', function (): void {
    PrismServer::register('nyx', fn (): \EchoLabs\Prism\Generators\TextGenerator => new TextGenerator);
    PrismServer::register('omni', fn (): \EchoLabs\Prism\Generators\TextGenerator => new TextGenerator);

    /** @var TestResponse */
    $response = $this->getJson('/prism/openai/v1/models');

    $response->assertOk();

    $response->assertJson([
        'object' => 'list',
        'data' => [
            ['object' => 'model', 'id' => 'nyx'],
            ['object' => 'model', 'id' => 'omni'],
        ],
    ]);
});

it('handles when there are no registred prism', function (): void {
    /** @var TestResponse */
    $response = $this->getJson('/prism/openai/v1/models');

    $response->assertOk();

    $response->assertJson([
        'object' => 'list',
        'data' => [],
    ]);
});
