<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\OpenAI\Handlers;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\OpenAI\Maps\MessageMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\ToolChoiceMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\ToolMap;
use EchoLabs\Prism\Text\Request;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
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
                'chat/completions',
                array_merge([
                    'model' => $request->model,
                    'messages' => (new MessageMap($request->messages, $request->systemPrompt ?? ''))(),
                    'max_completion_tokens' => $request->maxTokens ?? 2048,
                    'stream' => true,
                ], array_filter([
                    'temperature' => $request->temperature,
                    'top_p' => $request->topP,
                    'tools' => ToolMap::map($request->tools),
                    'tool_choice' => ToolChoiceMap::map($request->toolChoice),
                ]))
            );
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
