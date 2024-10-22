<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\OpenAI;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\Parts\ImagePart;
use EchoLabs\Prism\ValueObjects\Messages\Parts\TextPart;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use Exception;
use Illuminate\Support\Str;
use InvalidArgumentException;

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
            'content' => $message->content,
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
        $this->mappedMessages[] = [
            'role' => 'user',
            'content' => is_array($message->content)
            ? array_map(fn ($part): array => match ($part::class) {
                TextPart::class => ['type' => 'text', $part->text],
                ImagePart::class => [
                    'type' => 'image_url',
                    'image_url' => Str::isUrl($part->image)
                        ? $part->image
                        : vsprintf('data:%s;base64,%s', [
                            $part->mimeType,
                            $part->image,
                        ]),
                ],
                default => throw new InvalidArgumentException($part::class.' is not supported')
            }, $message->content)
            : $message->content,
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
            'content' => $message->text(),
            'tool_calls' => $toolCalls,
        ]);
    }
}
