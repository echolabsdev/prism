<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Mistral;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Client
{
    protected PendingRequest $client;

    public function __construct(
        public readonly string $url,
        public readonly ?string $apiKey,
    ) {
        $this->client = Http::withHeaders(array_filter([
            'Authorization' => $this->apiKey ? sprintf('Bearer %s', $this->apiKey) : null,
        ]))->baseUrl($this->url);
    }

    /**
     * @param  array<int, mixed>  $messages
     * @param  array<int, mixed>|null  $tools
     */
    public function messages(
        string $model,
        array $messages,
        ?int $maxTokens,
        int|float|null $temperature,
        int|float|null $topP,
        ?array $tools,
    ): Response {
        return $this->client->post(
            'chat/completions',
            array_merge([
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens ?? 2048,
            ], array_filter([
                'temperature' => $temperature,
                'top_p' => $topP,
                'tools' => $tools,
            ]))
        );
    }
}
