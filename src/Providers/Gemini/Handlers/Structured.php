<?php

namespace EchoLabs\Prism\Providers\Gemini\Handlers;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Enums\StructuredMode;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Gemini\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Gemini\Maps\MessageMap;
use EchoLabs\Prism\Providers\Gemini\Maps\ToolCallMap;
use EchoLabs\Prism\Providers\Gemini\Maps\ToolChoiceMap;
use EchoLabs\Prism\Providers\Gemini\Maps\ToolMap;
use EchoLabs\Prism\Providers\Gemini\Support\StructuredModeResolver;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Throwable;

class Structured
{
	public function __construct(protected PendingRequest $client) {}

	public function handle(Request $request): ProviderResponse
	{
		try {
			$response = $this->sendRequest($request);
		} catch (Throwable $e) {
			throw PrismException::providerRequestError($request->model, $e);
		}

		$data = $response->json();

		if (data_get($data, 'error') || ! $data) {
			throw PrismException::providerResponseError(vsprintf(
				'Gemini Error: [%s] %s',
				[
					data_get($data, 'error.code', 'unknown'),
					data_get($data, 'error.message', 'unknown'),
				]
			));
		}

		return new ProviderResponse(
			text: data_get($data, 'candidates.0.content.parts.0.text') ?? '',
			toolCalls: [],
			usage: new Usage(
				data_get($data, 'usageMetadata.promptTokenCount', 0),
				data_get($data, 'usageMetadata.candidatesTokenCount', 0)
			),
			finishReason: FinishReasonMap::map(data_get($data, 'candidates.0.finishReason')),
			response: [
				'id' => data_get($data, 'id'),
				'model' => data_get($data, 'modelVersion'),
			]
		);
	}

	public function sendRequest(Request $request): Response
	{
		$endpoint = "{$request->model}:generateContent";

		$payload = (new MessageMap($request->messages, $request->systemPrompt))();

		$generationConfig = array_filter([
			'temperature' => $request->temperature,
			'topP' => $request->topP,
			'maxOutputTokens' => $request->maxTokens,
			'response_mime_type' => "application/json",
			'response_schema' => $this->mapResponseFormat($request)
		]);

		if ($generationConfig !== []) {
			$payload['generationConfig'] = $generationConfig;
		}

		$safetySettings = data_get($request->providerMeta, 'safetySettings');
		if (! empty($safetySettings)) {
			$payload['safetySettings'] = $safetySettings;
		}

		return $this->client->post($endpoint, $payload);

	}

	/**
	 * @param  array<string, string>  $message
	 */
	protected function handleRefusal(array $message): void
	{
		if (! is_null(data_get($message, 'refusal', null))) {
			throw new PrismException(sprintf('Gemini Refusal: %s', $message['refusal']));
		}
	}

	/**
	 * @return array|null
	 */
	protected function mapResponseFormat(Request $request): ?array
	{
		// StructuredModeResolver not necessary for Gemini as there is no additional "modes"

		// WIP - as the ObjectSchema is not aware of the Provider, the
		$removeProperties = function ($array) use (&$removeProperties) {
			return collect($array)->map(function ($value, $key) use (&$removeProperties) {
				// If value is an array, recursively process it
				if (is_array($value)) {
					$value = $removeProperties($value);
				}

				return $value;
			})->reject(function ($value, $key) {
				return $key === 'additionalProperties'; // Gemini does not allow this value
			})->all();
		};


		return $removeProperties($request->schema->toArray());

	}

	protected function appendMessageForJsonMode(Request $request): Request
	{
		return $request->addMessage(new SystemMessage(sprintf(
			"Respond with JSON that matches the following schema: \n %s",
			json_encode($request->schema->toArray(), JSON_PRETTY_PRINT)
		)));
	}
}
