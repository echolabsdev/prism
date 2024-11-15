<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Providers\Anthropic\Handlers\Text;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Requests\TextRequest;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Anthropic implements Provider
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $apiVersion,
    ) {}

    #[\Override]
    public function text(TextRequest $request): ProviderResponse
    {
        $handler = new Text($this->client($request->clientOptions));

        return $handler->handle($request);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function client(array $options = []): PendingRequest
    {
        return Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->apiVersion,
        ])
            ->withOptions($options)
            ->baseUrl('https://api.anthropic.com/v1');
    }
}
