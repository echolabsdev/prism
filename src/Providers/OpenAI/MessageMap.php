<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\OpenAI;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\ImageCall;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ImageCall;
use EchoLabs\Prism\ValueObjects\ToolCall;
use Exception;

class MessageMap
{
    /** @var array<int, mixed> */
    protected $mappedMessages = [];

    /**
     * @param  array<int, Message>  $messages
     */
    public function __construct(
        protected array $messages,
        protected string $systemPrompt
    ) {
        if ($systemPrompt !== '' && $systemPrompt !== '0') {
            $this->messages = array_merge(
                [new SystemMessage($systemPrompt)],
                $this->messages
            );
        }
    }

    /**
     * @return array<int, mixed>
     */
    public function __invoke(): array
    {
        array_map(
            fn (Message $message) => $this->mapMessage($message),
            $this->messages
        );

        return $this->mappedMessages;
    }

    public function mapMessage(Message $message): void
    {
        match ($message::class) {
            UserMessage::class => $this->mapUserMessage($message),
            AssistantMessage::class => $this->mapAssistantMessage($message),
            ToolResultMessage::class => $this->mapToolResultMessage($message),
            SystemMessage::class => $this->mapSystemMessage($message),
            default => throw new Exception('Could not map message type '.$message::class),
        };
    }

    protected function mapSystemMessage(SystemMessage $message): void
    {
        $this->mappedMessages[] = [
            'role' => 'system',
            'content' => $message->content(),
        ];
    }

    protected function mapToolResultMessage(ToolResultMessage $message): void
    {
        foreach ($message->toolResults as $toolResult) {
            $this->mappedMessages[] = [
                'role' => 'tool',
                'tool_call_id' => $toolResult->toolCallId,
                'content' => $toolResult->result,
            ];
        }
    }

    protected function mapUserMessage(UserMessage $message): void
    {
        if ($message->hasImages()) {
            $this->mappedMessages[] = [
                'role' => 'user',
                'content' => [
                    ...array_map(fn (ImageCall $image) => [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $image->url,
                        ],
                    ], $message->images),
                    [
                        'type' => 'text',
                        'text' => $message->content(),
                    ],
                ],
            ];
            return;
        }

        $this->mappedMessages[] = [
            'role' => 'user',
            'content' => $message->content(),
        ];
    }

    protected function mapAssistantMessage(AssistantMessage $message): void
    {
        $toolCalls = array_map(fn (ToolCall $toolCall): array => [
            'id' => $toolCall->id,
            'type' => 'function',
            'function' => [
                'name' => $toolCall->name,
                'arguments' => json_encode($toolCall->arguments()),
            ],
        ], $message->toolCalls());

        $this->mappedMessages[] = array_filter([
            'role' => 'assistant',
            'content' => $message->content(),
            'tool_calls' => $toolCalls,
        ]);
    }
}
