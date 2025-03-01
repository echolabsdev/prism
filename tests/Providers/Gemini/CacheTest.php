<?php

declare(strict_types=1);

namespace Tests\Providers\Gemini;

use PrismPHP\Prism\Enums\Provider;
use PrismPHP\Prism\Prism;
use PrismPHP\Prism\Providers\Gemini\Gemini;
use PrismPHP\Prism\ValueObjects\Messages\Support\Document;
use PrismPHP\Prism\ValueObjects\Messages\SystemMessage;
use PrismPHP\Prism\ValueObjects\Messages\UserMessage;
use Tests\Fixtures\FixtureResponse;

it('can store a document in the cache', function (): void {
    $file = file_get_contents('https://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=CELEX:12012E/TXT:en:PDF');

    FixtureResponse::fakeResponseSequence('*', 'gemini/create-cache');

    /** @var Gemini */
    $provider = Prism::provider(Provider::Gemini);

    $object = $provider->cache(
        model: 'gemini-1.5-flash-002',
        messages: [
            new UserMessage('', [
                Document::fromBase64(base64_encode($file), 'application/pdf'),
            ]),
        ],
        systemPrompts: [
            new SystemMessage('You are a legal analyst.'),
        ],
        ttl: 60
    );

    expect($object->model)->toBe('gemini-1.5-flash-002');
    expect($object->name)->toBe('cachedContents/kmvaiarhyq2g');
    expect($object->tokens)->toBe(88759);
    expect($object->expiresAt->toIsoString())->toBe('2025-03-01T11:24:58.504522Z');
});
