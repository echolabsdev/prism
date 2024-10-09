<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Drivers\Gemini;

use EchoLabs\Prism\Contracts\Driver;
use EchoLabs\Prism\Drivers\DriverResponse;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Requests\TextRequest;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
use EchoLabs\Prism\ValueObjects\Usage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use Throwable;

class Gemini implements Driver
{
    protected Client $client;
    protected string $model;
    private const int MAX_ITERATIONS = 5; // A reasonable default, adjust as needed

    public function __construct(protected readonly string $apiKey)
    {
        $this->client = new Client($this->apiKey);
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
        $messageMap = new GeminiMessageMap($request->messages);
        $mappedMessages = $messageMap();
        $tools = GeminiTool::map($request->tools);
        $toolMap = collect($request->tools)->keyBy->name();

        $finalText = '';
        $allToolCalls = [];
        $totalPromptTokens = 0;
        $totalCompletionTokens = 0;
        $step = 0;
        $lastFinishReason = FinishReason::Unknown;

        do {
            try {
                $response = $this->client->messages(
                    model: $this->model,
                    systemInstruction: $mappedMessages['system_instruction'] ?? null,
                    contents: $mappedMessages['contents'],
                    tools: $tools,
                    generationConfig: [
                        'temperature' => $request->temperature,
                        'topP' => $request->topP,
                        'topK' => $request->topK ?? null,
                        'maxOutputTokens' => $request->maxTokens,
                    ]
                );
            } catch (Throwable $e) {
                throw PrismException::providerRequestError($this->model, $e);
            }

            $data = $response->json();

            if (isset($data['error'])) {
                throw PrismException::providerResponseError(
                    "Gemini Error: [{$data['error']['code']}] {$data['error']['message']}"
                );
            }

            $candidates = $data['candidates'][0];
            $content = $candidates['content'];

            $stepText = '';
            $stepToolCalls = [];
            $stepToolResults = [];

            foreach ($content['parts'] as $part) {
                if (isset($part['text'])) {
                    $stepText .= $part['text'];
                }

                if (isset($part['functionCall'])) {
                    $toolCall = new ToolCall(
                        id: $part['functionCall']['name'] . '_' . $step,
                        name: $part['functionCall']['name'],
                        arguments: $part['functionCall']['args']
                    );
                    $stepToolCalls[] = $toolCall;

                    // Execute the tool
                    if ($tool = $toolMap->get($toolCall->name)) {
                        $result = $tool->handle(...$toolCall->arguments());
                        $toolResult = new ToolResult($toolCall->id, $toolCall->name, $toolCall->arguments(), $result);
                        $stepToolResults[] = $toolResult;

                        // Add tool result to the conversation
                        $mappedMessages['contents'][] = [
                            'role' => 'user',
                            'parts' => [['text' => "Tool result for {$toolCall->name}: {$result}"]]
                        ];
                    }
                }
            }

            $finalText .= $stepText;
            $allToolCalls = array_merge($allToolCalls, $stepToolCalls);

            if ($stepToolResults !== []) {
                $mappedMessages['contents'][] = [
                    'role' => 'user',
                    'parts' => [['text' => (new ToolResultMessage($stepToolResults))->content()]]
                ];
            }

            $totalPromptTokens += $data['usageMetadata']['promptTokenCount'];
            $totalCompletionTokens += $data['usageMetadata']['candidatesTokenCount'];

            $lastFinishReason = $this->mapFinishReason($candidates['finishReason']);
            $step++;

            // Stop if we've reached the maximum number of tokens
            if ($totalPromptTokens + $totalCompletionTokens >= $request->maxTokens) {
                $lastFinishReason = FinishReason::Length;
                break;
            }
        } while ($stepToolCalls !== [] && $step < self::MAX_ITERATIONS);

        return new DriverResponse(
            text: $finalText,
            toolCalls: $allToolCalls,
            usage: new Usage($totalPromptTokens, $totalCompletionTokens),
            finishReason: $lastFinishReason,
            response: [
                'model' => $this->model,
                'id' => (string) time(),
            ]
        );
    }

    protected function mapFinishReason(string $finishReason): FinishReason
    {
        return match ($finishReason) {
            'STOP' => FinishReason::Stop,
            'MAX_TOKENS' => FinishReason::Length,
            'SAFETY' => FinishReason::ContentFilter,
            'RECITATION' => FinishReason::ContentFilter,
            'OTHER' => FinishReason::Unknown,
            default => FinishReason::Unknown,
        };
    }
}