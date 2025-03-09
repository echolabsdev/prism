<?php

declare(strict_types=1);

namespace Tests\Providers\OpenAI;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PrismPHP\Prism\Enums\Provider;
use PrismPHP\Prism\Exceptions\PrismRateLimitedException;
use PrismPHP\Prism\Prism;
use PrismPHP\Prism\Providers\OpenAI\Concerns\ValidatesResponse;
use PrismPHP\Prism\ValueObjects\ProviderRateLimit;

arch()->expect([
    'Providers\OpenAI\Handlers\Text',
    'Providers\OpenAI\Handlers\Structured',
])
    ->toUseTrait(ValidatesResponse::class);

it('throws a PrismRateLimitedException with a 429 response code', function (): void {
    Http::fake([
        '*' => Http::response(
            status: 429,
        ),
    ])->preventStrayRequests();

    Prism::text()
        ->using(Provider::OpenAI, 'fake-model')
        ->withPrompt('Hello world!')
        ->generate();

})->throws(PrismRateLimitedException::class);

it('sets the correct data on the PrismRateLimitedException', function (): void {
    $this->freezeTime(function (Carbon $time): void {
        $time = $time->toImmutable();
        Http::fake([
            '*' => Http::response(
                status: 429,
                headers: [
                    'x-ratelimit-limit-requests' => 60,
                    'x-ratelimit-limit-tokens' => 150000,
                    'x-ratelimit-remaining-requests' => 0,
                    'x-ratelimit-remaining-tokens' => 149984,
                    'x-ratelimit-reset-requests' => '1s',
                    'x-ratelimit-reset-tokens' => '6m30s',
                ]
            ),
        ])->preventStrayRequests();

        try {
            Prism::text()
                ->using(Provider::OpenAI, 'fake-model')
                ->withPrompt('Hello world!')
                ->generate();
        } catch (PrismRateLimitedException $e) {
            expect($e->retryAfter)->toEqual(null);
            expect($e->rateLimits)->toHaveCount(2);
            expect($e->rateLimits[0])->toBeInstanceOf(ProviderRateLimit::class);
            expect($e->rateLimits[0]->name)->toEqual('requests');
            expect($e->rateLimits[0]->limit)->toEqual(60);
            expect($e->rateLimits[0]->remaining)->toEqual(0);
            expect($e->rateLimits[0]->resetsAt->equalTo($time->addSeconds(1)))->toBeTrue();

            expect($e->rateLimits[1]->name)->toEqual('tokens');
            expect($e->rateLimits[1]->limit)->toEqual(150000);
            expect($e->rateLimits[1]->remaining)->toEqual(149984);
            expect($e->rateLimits[1]->resetsAt->equalTo($time->addMinutes(6)->addSeconds(30)))->toBeTrue();
        }
    });
});
