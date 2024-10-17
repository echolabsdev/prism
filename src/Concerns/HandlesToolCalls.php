<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Tool;
use EchoLabs\Prism\ValueObjects\ToolCall;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;
use Throwable;

trait HandlesToolCalls
{
    /**
     * @param  array<int, Tool>  $tools
     */
    protected function handleToolCall(array $tools, ToolCall $toolCall): ?string
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
