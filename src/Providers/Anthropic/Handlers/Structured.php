<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Handlers;

use EchoLabs\Prism\Contracts\PrismRequest;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Providers\Anthropic\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\MessageMap;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Structured\Response;
use EchoLabs\Prism\Structured\ResponseBuilder;
use EchoLabs\Prism\Structured\Step;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Support\Collection;

class Structured extends AnthropicHandlerAbstract
{
    /**
     * @var StructuredRequest
     */
    protected PrismRequest $request; // Redeclared for type hinting

    protected Response $tempResponse;

    protected ResponseBuilder $responseBuilder;

    public function __construct(mixed ...$args)
    {
        parent::__construct(...$args);

        $this->responseBuilder = new ResponseBuilder;
    }

    public function generate(): Response
    {
        $this->appendMessageForJsonMode();

        $this->sendRequest();

        $this->prepareTempResponse();

        $responseMessage = new AssistantMessage(
            $this->tempResponse->text,
            [],
            $this->tempResponse->additionalContent
        );

        $this->request->addMessage($responseMessage);
        $this->responseBuilder->addResponseMessage($responseMessage);

        $this->responseBuilder->addStep(new Step(
            text: $this->tempResponse->text,
            object: json_decode($this->tempResponse->text, true),
            finishReason: $this->tempResponse->finishReason,
            usage: $this->tempResponse->usage,
            responseMeta: $this->tempResponse->responseMeta,
            messages: $this->request->messages(),
            systemPrompts: $this->request->systemPrompts(),
            additionalContent: $this->tempResponse->additionalContent,
        ));

        return $this->responseBuilder->toResponse();
    }

    /**
     * @param  StructuredRequest  $request
     * @return array<string, mixed>
     */
    #[\Override]
    public static function buildHttpRequestPayload(PrismRequest $request): array
    {
        if (! $request->is(StructuredRequest::class)) {
            throw new \InvalidArgumentException('Request must be an instance of '.StructuredRequest::class);
        }

        return array_merge([
            'model' => $request->model(),
            'messages' => MessageMap::map($request->messages(), $request->providerMeta(Provider::Anthropic)),
            'max_tokens' => $request->maxTokens(),
        ], array_filter([
            'system' => MessageMap::mapSystemMessages($request->systemPrompts()),
            'temperature' => $request->temperature(),
            'top_p' => $request->topP(),
        ]));
    }

    protected function prepareTempResponse(): void
    {
        $data = $this->httpResponse->json();

        $this->tempResponse = new Response(
            steps: new Collection,
            responseMessages: new Collection,
            text: $this->extractText($data),
            structured: [],
            finishReason: FinishReasonMap::map(data_get($data, 'stop_reason', '')),
            usage: new Usage(
                promptTokens: data_get($data, 'usage.input_tokens'),
                completionTokens: data_get($data, 'usage.output_tokens'),
                cacheWriteInputTokens: data_get($data, 'usage.cache_creation_input_tokens', null),
                cacheReadInputTokens: data_get($data, 'usage.cache_read_input_tokens', null)
            ),
            responseMeta: new ResponseMeta(
                id: data_get($data, 'id'),
                model: data_get($data, 'model'),
                rateLimits: $this->processRateLimits()
            ),
            additionalContent: array_filter([
                'messagePartsWithCitations' => $this->extractCitations($data),
            ])
        );
    }

    protected function appendMessageForJsonMode(): void
    {
        $this->request->addMessage(new UserMessage(sprintf(
            "Respond with ONLY JSON that matches the following schema: \n %s %s",
            json_encode($this->request->schema()->toArray(), JSON_PRETTY_PRINT),
            ($this->request->providerMeta(Provider::Anthropic)['citations'] ?? false)
                ? "\n\n Return the JSON as a single text block with a single set of citations."
                : ''
        )));
    }
}
