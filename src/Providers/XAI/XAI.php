<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\XAI;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Providers\XAI\Handlers\Text;
use EchoLabs\Prism\Text\Request;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class XAI implements Provider
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $url,
    ) {}

    #[\Override]
    public function text(Request $request): ProviderResponse
    {
        $handler = new Text($this->client($request->clientOptions));

        return $handler->handle($request);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function client(array $options = []): PendingRequest
    {
        return Http::withHeaders(array_filter([
            'Authorization' => $this->apiKey !== '' && $this->apiKey !== '0' ? sprintf('Bearer %s', $this->apiKey) : null,
        ]))
            ->withOptions($options)
            ->baseUrl($this->url);
    }
}
