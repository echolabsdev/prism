<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Tool;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;
use Throwable;

trait CallsTools
{
    /**
     * @param  Tool[]  $tools
     * @param  ToolCalls[]  $toolCalls
     * @return ToolResult[]
     */
    protected function callTools(array $tools, array $toolCalls): array
    {
        return array_map(
            function (ToolCall $toolCall) use ($tools): ToolResult {
                $tool = $this->locateCalledTool($toolCall->name, $tools);

                try {
                    $result = call_user_func_array(
                        $tool->handle(...),
                        $toolCall->arguments()
                    );

                    return new ToolResult(
                        toolCallId: $toolCall->id,
                        toolName: $toolCall->name,
                        args: $toolCall->arguments(),
                        result: $result,
                    );
                } catch (Throwable $e) {
                    throw PrismException::toolCallFailed($toolCall, $e);
                }

            },
            $toolCalls
        );
    }

    protected function locateCalledTool(string $name, array $tools): Tool
    {
        try {
            return collect($tools)
                ->sole(fn (Tool $tool): bool => $tool->name() === $name);
        } catch (ItemNotFoundException $e) {
            throw PrismException::toolNotFound($name, $e);
        } catch (MultipleItemsFoundException $e) {
            throw PrismException::multipleToolsFound($name, $e);
        }
    }
}