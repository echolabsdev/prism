<?php

declare(strict_types=1);

namespace Tests\Providers\OpenAI;

use EchoLabs\Prism\Providers\Mistral\Tool as MistralTool;
use EchoLabs\Prism\Tool;

it('maps tools', function (): void {
    $tool = (new Tool)
        ->as('search')
        ->for('Searching the web')
        ->withStringParameter('query', 'the detailed search query')
        ->using(fn (): string => '[Search results]');

    expect(MistralTool::toArray($tool))->toBe([
        'type' => 'function',
        'function' => [
            'name' => $tool->name(),
            'description' => $tool->description(),
            'parameters' => [
                'type' => 'object',
                'properties' => $tool->parameters(),
                'required' => $tool->requiredParameters(),
            ],
        ],
    ]);
});
