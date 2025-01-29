<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Gemini\Maps;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\Support\Image;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use Exception;

class MessageMap
{
    /** @var array<string, mixed> */
    protected array $contents = [];

    /**
     * @param  array<int, Message>  $messages
     */
    public function __construct(
        protected array $messages,
        protected ?string $systemPrompt = null
    ) {
        if ($systemPrompt !== null && $systemPrompt !== '' && $systemPrompt !== '0') {
            $this->messages = array_merge(
                [new SystemMessage($systemPrompt)],
                $this->messages
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $this->contents['contents'] = [];

        foreach ($this->messages as $message) {
            $this->mapMessage($message);
        }

        return array_filter($this->contents);
    }

    protected function mapMessage(Message $message): void
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
        $this->contents['system_instruction'] = [
            'parts' => [
                [
                    'text' => $message->content,
                ],
            ],
        ];
    }

    protected function mapToolResultMessage(ToolResultMessage $message): void
    {
        $parts = [];
        foreach ($message->toolResults as $toolResult) {
            $parts[] = [
                'functionResponse' => [
                    'name' => $toolResult->toolName,
                    'response' => [
                        'name' => $toolResult->toolName,
                        'content' => json_encode($toolResult->result),
                    ],
                ],
            ];
        }

        $this->contents['contents'][] = [
            'role' => 'user',
            'parts' => $parts,
        ];
    }

    protected function mapUserMessage(UserMessage $message): void
    {
        $parts = [];

        if ($message->text()) {
            $parts[] = ['text' => $message->text()];
        }

        $imageParts = array_map(fn (Image $image): array => [
            'inline_data' => [
                'mime_type' => $image->mimeType,
                'data' => $this->getImageData($image),
            ],
        ], $message->images());

        $parts = array_merge($parts, $imageParts);

        $this->contents['contents'][] = [
            'role' => 'user',
            'parts' => $parts,
        ];
    }

    protected function mapAssistantMessage(AssistantMessage $message): void
    {
        $parts = [];

        if ($message->content) {
            $parts[] = ['text' => $message->content];
        }

        if ($message->toolCalls) {
            foreach ($message->toolCalls as $toolCall) {
                $parts[] = [
                    'functionCall' => [
                        'name' => $toolCall->name,
                        'args' => $toolCall->arguments(),
                    ],
                ];
            }
        }

        $this->contents['contents'][] = [
            'role' => 'model',
            'parts' => $parts,
        ];
    }

    protected function getImageData(Image $image): string
    {
        if ($image->isUrl()) {
            /** @var string $response */
            $response = file_get_contents($image->image);

            return base64_encode($response);
        }

        return $image->image;
    }
}
