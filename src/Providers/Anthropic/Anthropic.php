<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\DriverResponse;
use EchoLabs\Prism\Requests\TextRequest;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\Usage;
use Throwable;

class Anthropic implements Provider
{
    protected Client $client;

    protected string $model;

    public function __construct(
        public readonly string $apiKey,
        public readonly string $apiVersion,
    ) {
        $this->client = new Client(
            $this->apiKey,
            $this->apiVersion,
        );
    }

    #[\Override]
    public static function make(string $model): Provider
    {
        return (new self(
            apiKey: config('prism.providers.anthropic.api_key'),
            apiVersion: config('prism.providers.anthropic.api_version'),
        ))->usingModel($model);
    }

    #[\Override]
    public function usingModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    #[\Override]
    public function text(TextRequest $request): DriverResponse
    {
        try {
            $response = $this->client->messages(
                model: $this->model,
                systemPrompt: $request->systemPrompt,
                messages: (new AnthropicMessageMap($request->messages))(),
                maxTokens: $request->maxTokens,
                temperature: $request->temperature,
                topP: $request->topP,
                tools: AnthropicTool::map($request->tools),
            );
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($this->model, $e);
        }

        $data = $response->json();

        if (data_get($data, 'type') === 'error') {
            throw PrismException::providerResponseError(vsprintf(
                'Anthropic Error: [%s] %s',
                [
                    data_get($data, 'error.type', 'unknown'),
                    data_get($data, 'error.message'),
                ]
            ));
        }

        $text = '';
        $toolCalls = [];

        foreach (data_get($data, 'content', []) as $content) {
            if (data_get($content, 'type') === 'text') {
                $text .= data_get($content, 'text');
            }

            if (data_get($content, 'type') === 'tool_use') {
                $toolCalls[] = new ToolCall(
                    id: data_get($content, 'id'),
                    name: data_get($content, 'name'),
                    arguments: data_get($content, 'input'),
                );
            }
        }

        return new DriverResponse(
            text: $text,
            toolCalls: $toolCalls,
            usage: new Usage(
                data_get($data, 'usage.input_tokens'),
                data_get($data, 'usage.output_tokens'),
            ),
            finishReason: $this->mapFinishReason(data_get($data, 'stop_reason', '')),
            response: [
                'id' => data_get($data, 'id'),
                'model' => data_get($data, 'model'),
            ]
        );
    }

    protected function mapFinishReason(string $stopReason): FinishReason
    {
        return match ($stopReason) {
            'end_turn', 'stop_sequence' => FinishReason::Stop,
            'tool_use' => FinishReason::ToolCalls,
            'max_tokens' => FinishReason::Length,
            default => FinishReason::Unknown,
        };
    }
}
