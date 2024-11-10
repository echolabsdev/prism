<?php

declare(strict_types=1);

namespace Tests;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\PrismManager;
use EchoLabs\Prism\Providers\Anthropic\Anthropic;
use EchoLabs\Prism\Providers\Mistral\Mistral;
use EchoLabs\Prism\Providers\Ollama\Ollama;
use EchoLabs\Prism\Providers\OpenAI\OpenAI;
use EchoLabs\Prism\Providers\XAI\XAI;

it('can resolve Anthropic', function (): void {
    $manager = new PrismManager($this->app);

    expect($manager->resolve(Provider::Anthropic))->toBeInstanceOf(Anthropic::class);
    expect($manager->resolve('anthropic'))->toBeInstanceOf(Anthropic::class);
});

it('can resolve Ollama', function (): void {
    $manager = new PrismManager($this->app);

    expect($manager->resolve(Provider::Ollama))->toBeInstanceOf(Ollama::class);
    expect($manager->resolve('ollama'))->toBeInstanceOf(Ollama::class);
});

it('can resolve OpenAI', function (): void {
    $manager = new PrismManager($this->app);

    expect($manager->resolve(Provider::OpenAI))->toBeInstanceOf(OpenAI::class);
    expect($manager->resolve('openai'))->toBeInstanceOf(OpenAI::class);
});

it('can resolve Mistral', function (): void {
    $manager = new PrismManager($this->app);

    expect($manager->resolve(Provider::Mistral))->toBeInstanceOf(Mistral::class);
    expect($manager->resolve('mistral'))->toBeInstanceOf(Mistral::class);
});

it('can resolve XAI', function (): void {
    $manager = new PrismManager($this->app);

    expect($manager->resolve(Provider::XAI))->toBeInstanceOf(XAI::class);
    expect($manager->resolve('xai'))->toBeInstanceOf(XAI::class);
});
