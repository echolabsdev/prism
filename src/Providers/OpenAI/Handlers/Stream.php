<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\OpenAI\Handlers;

use EchoLabs\Prism\Concerns\CallsTools;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\OpenAI\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\MessageMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\ToolChoiceMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\ToolMap;
use EchoLabs\Prism\Stream\Chunk;
use EchoLabs\Prism\Text\Request;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
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
    use CallsTools;

    public function __construct(protected PendingRequest $client) {}

    /**
     * @return Generator<ProviderResponse>
     */
    public function handle(Request $request): Generator
    {
        ray('called');
        $response = $this->sendRequest($request);

        // TODO: response validation?

        $text = '';
        $toolCalls = [];

        while (! $response->getBody()->eof()) {
            $line = $this->readLine($response->getBody());

            if (! str_starts_with($line, 'data:')) {
                continue;
            }

            $line = trim(substr($line, strlen('data: ')));

            if (Str::contains($line, 'DONE')) {
                continue;
            }

            $data = json_decode(
                $line,
                true,
                flags: JSON_THROW_ON_ERROR
            );

            // ray('raw_data', $data);

            if (data_get($data, 'choices.0.delta.tool_calls')) {
                foreach (data_get($data, 'choices.0.delta.tool_calls') as $index => $toolCall) {
                    if ($name = data_get($toolCall, 'function.name')) {
                        $toolCalls[$index]['name'] = $name;
                        $toolCalls[$index]['arguments'] = '';
                        $toolCalls[$index]['id'] = data_get($toolCall, 'id');
                    }

                    if ($arguments = data_get($toolCall, 'function.arguments')) {
                        $toolCalls[$index]['arguments'] .= $arguments;
                    }
                }

                continue;
            }

            if ($this->mapFinishReason($data) === FinishReason::ToolCalls) {
                $toolCalls = collect($toolCalls)
                    ->map(fn ($toolCall): ToolCall => new ToolCall(
                        data_get($toolCall, 'id'),
                        data_get($toolCall, 'name'),
                        data_get($toolCall, 'arguments'),
                    ));

                $toolResults = $this->callTools($request->tools(), $toolCalls->toArray());

                $request->addMessage(new AssistantMessage(
                    $text,
                    $toolCalls->toArray(),
                ));
                $request->addMessage(new ToolResultMessage($toolResults));

                yield new Chunk(
                    text: '',
                    toolCalls: $toolCalls->toArray(),
                    toolResults: $toolResults,
                );

                yield from $this->handle($request);

                return;
            }

            if ($this->mapFinishReason($data) === FinishReason::Stop) {
                $text .= data_get($data, 'choices.0.delta.content', '');

                yield new Chunk(
                    text: data_get($data, 'choices.0.delta.content', ''),
                    finishReason: FinishReasonMap::map(data_get($data, 'choices.0.finish_reason')),
                );

                continue;
            }

            $text .= data_get($data, 'choices.0.delta.content', '');
            yield new Chunk(
                text: data_get($data, 'choices.0.delta.content', '') ?? '',
                finishReason: null
            );
        }
    }

    /**
     * @param  array<int, mixed>  $data
     */
    protected function mapFinishReason(array $data): FinishReason
    {
        return FinishReasonMap::map(data_get($data, 'choices.0.finish_reason') ?? '');
    }

    protected function sendRequest(Request $request): Response
    {
        try {
            return $this
                ->client
                ->withOptions(['stream' => true])
                ->post(
                    'chat/completions',
                    array_merge([
                        'stream' => true,
                        'model' => $request->model(),
                        'messages' => (new MessageMap($request->messages(), $request->systemPrompts()))(),
                        'max_completion_tokens' => $request->maxTokens(),
                    ], array_filter([
                        'temperature' => $request->temperature(),
                        'top_p' => $request->topP(),
                        'tools' => ToolMap::map($request->tools()),
                        'tool_choice' => ToolChoiceMap::map($request->toolChoice()),
                    ]))
                );
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model(), $e);
        }
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
