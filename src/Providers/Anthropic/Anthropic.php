<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Providers\Anthropic\Handlers\Text;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Text\Request;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Anthropic implements Provider
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $apiVersion,
        public readonly bool $cacheControl = false
    ) {}

    #[\Override]
    public function text(Request $request): ProviderResponse
    {
        $handler = new Text($this->client($request->clientOptions), $this);

        return $handler->handle($request);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function client(array $options = []): PendingRequest
    {
        return Http::withHeaders($this->getHeaders())
            ->withOptions($options)
            ->baseUrl('https://api.anthropic.com/v1');
    }

    /** @return  array<string, string> */
    protected function getHeaders(): array
    {
        $headers = [
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->apiVersion,
        ];

        if ($this->cacheControl) {
            $headers['anthropic-beta'] = 'prompt-caching-2024-07-31';
        }

        return $headers;
    }
}
