<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Tool;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;
use Throwable;

trait HandlesToolCalls
{
    /**
     * @return array<int, ToolResult>
     */
    protected function handleToolCalls(ProviderResponse $response): array
    {
        $toolResults = array_map(function (ToolCall $toolCall): ToolResult {
            $result = $this->callTools($this->tools, $toolCall);

            return new ToolResult(
                toolCallId: $toolCall->id,
                toolName: $toolCall->name,
                args: $toolCall->arguments(),
                result: $result,
            );
        }, $response->toolCalls);

        $resultMessage = new ToolResultMessage($toolResults);

        $this->messages[] = $resultMessage;
        $this->responseBuilder->addResponseMessage($resultMessage);

        return $toolResults;
    }

    /**
     * @param  array<int, Tool>  $tools
     */
    protected function callTools(array $tools, ToolCall $toolCall): ?string
    {
        try {
            /** @var Tool $tool */
            $tool = collect($tools)
                ->sole(fn (Tool $tool): bool => $tool->name() === $toolCall->name);

            return call_user_func_array($tool->handle(...), $toolCall->arguments());
        } catch (ItemNotFoundException $e) {
            throw PrismException::toolNotFound($toolCall, $e);
        } catch (MultipleItemsFoundException $e) {
            throw PrismException::multipleToolsFound($toolCall, $e);
        } catch (Throwable $e) {
            throw PrismException::toolCallFailed($toolCall, $e);
        }
    }
}
