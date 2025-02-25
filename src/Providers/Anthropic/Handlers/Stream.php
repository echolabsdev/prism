<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Handlers;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Anthropic\Maps\MessageMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\ToolChoiceMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\ToolMap;
use EchoLabs\Prism\Text\Request;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ToolCall;
use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Psr\Http\Message\StreamInterface;
use Throwable;

class Stream
{
    public function __construct(protected PendingRequest $client) {}

    /**
     * @return Generator<ProviderResponse>
     */
    public function handle(Request $request): Generator
    {
        try {
            $response = $this->sendRequest($request);

            while (! $response->getBody()->eof()) {
                $line = $this->readLine($response->getBody());

                // NOTE: This should mean an error
                if (json_validate($line)) {
                    $data = json_decode($line, true);

                    if (data_get($data, 'type') === 'error') {
                        throw new PrismException(
                            sprintf('Anthropic Error: %s', data_get($data, 'error.message'))
                        );
                    }

                    continue;
                }

                ray('raw_line', $line);

                if (! str_starts_with($line, 'data:')) {
                    continue;
                }

                $line = trim(substr($line, strlen('data: ')));

                if (Str::contains($line, 'DONE')) {
                    continue;
                }

                ray('parsed_line_data', $line);

                $data = json_decode(
                    $line,
                    true,
                    flags: JSON_THROW_ON_ERROR
                );

                ray('raw_json_data', $data);

                yield $line;
            }

        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }
    }

    public function sendRequest(Request $request): Response
    {
        return $this
            ->client
            ->withOptions(['stream' => true])
            ->post(
                'messages',
                array_merge([
                    'model' => $request->model,
                    'messages' => MessageMap::map($request->messages),
                    'max_tokens' => $request->maxTokens ?? 2048,
                    'stream' => true,
                ], array_filter([
                    'system' => MessageMap::mapSystemMessages($request->messages),
                    'temperature' => $request->temperature,
                    'top_p' => $request->topP,
                    'tools' => ToolMap::map($request->tools),
                    'tool_choice' => ToolChoiceMap::map($request->toolChoice),
                ]))
            );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractText(array $data): string
    {
        return array_reduce(data_get($data, 'content', []), function (string $text, array $content): string {
            if (data_get($content, 'type') === 'text') {
                $text .= data_get($content, 'text');
            }

            return $text;
        }, '');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return ToolCall[]
     */
    protected function extractToolCalls(array $data): array
    {
        $toolCalls = array_map(function ($content) {
            if (data_get($content, 'type') === 'tool_use') {
                return new ToolCall(
                    id: data_get($content, 'id'),
                    name: data_get($content, 'name'),
                    arguments: data_get($content, 'input')
                );
            }
        }, data_get($data, 'content', []));

        return array_values(array_filter($toolCalls));
    }

    protected function readLine(StreamInterface $stream): string
    {
        $buffer = '';

        while (! $stream->eof()) {
            if ('' === ($byte = $stream->read(1))) {
                return $buffer;
            }
            $buffer .= $byte;
            if ($byte === "\n") {
                break;
            }
        }

        return $buffer;
    }
}
