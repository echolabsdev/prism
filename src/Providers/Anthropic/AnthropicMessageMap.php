<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
use Exception;

class AnthropicMessageMap
{
    /**
     * @param  array<int, Message>  $messages
     */
    public function __construct(public readonly array $messages) {}

    /**
     * @return array<int, mixed>
     */
    public function __invoke(): array
    {
        return array_map(fn (Message $message): array => $this->mapMessage($message), $this->messages);
    }

    /**
     * @return array<string, mixed>
     */
    public function mapMessage(Message $message): array
    {
        return match ($message::class) {
            UserMessage::class => $this->mapUserMessage($message),
            AssistantMessage::class => $this->mapAssistantMessage($message),
            ToolResultMessage::class => $this->mapToolResultMessage($message),
            SystemMessage::class => throw new Exception('Anthropic does not support '.SystemMessage::class),
            default => throw new Exception('Could not map message type '.$message::class),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapToolResultMessage(ToolResultMessage $message): array
    {
        return [
            'role' => 'user',
            'content' => array_map(fn (ToolResult $toolResult): array => [
                'type' => 'tool_result',
                'tool_use_id' => $toolResult->toolCallId,
                'content' => $toolResult->result,
            ], $message->toolResults),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function mapUserMessage(UserMessage $message): array
    {
        return [
            'role' => 'user',
            'content' => $message->content(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapAssistantMessage(AssistantMessage $message): array
    {
        if ($message->hasToolCall()) {
            $content = [];

            if ($message->content() !== '' && $message->content() !== '0') {
                $content[] = [
                    'type' => 'text',
                    'text' => $message->content(),
                ];
            }

            $toolCalls = array_map(fn (ToolCall $toolCall): array => [
                'type' => 'tool_use',
                'id' => $toolCall->id,
                'name' => $toolCall->name,
                'input' => $toolCall->arguments(),
            ], $message->toolCalls());

            return [
                'role' => 'assistant',
                'content' => array_merge($content, $toolCalls),
            ];
        }

        return [
            'role' => 'assistant',
            'content' => $message->content(),
        ];
    }
}
