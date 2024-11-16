<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\OpenAI;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Providers\OpenAI\Handlers\Text;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Text\Request;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class OpenAI implements Provider
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $url,
        public readonly ?string $organization,
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
            'OpenAI-Organization' => $this->organization,
        ]))
            ->withOptions($options)
            ->baseUrl($this->url);
    }
}
