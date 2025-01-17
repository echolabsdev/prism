<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Maps;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\Support\Image;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
use Exception;
use InvalidArgumentException;
use UnitEnum;

class MessageMap
{
    /**
     * @param  array<int, Message>  $messages
     * @return array<int, mixed>
     */
    public static function map(array $messages): array
    {
        return array_values(array_map(
            fn (Message $message): array => self::mapMessage($message),
            array_filter($messages, fn (Message $message): bool => ! $message instanceof SystemMessage)
        ));
    }

    /**
     * @param  array<int, Message>  $messages
     * @return array<int, mixed>
     */
    public static function mapSystemMessages(array $messages, ?string $systemPrompt): array
    {
        return array_values(array_merge(
            $systemPrompt !== null ? [self::mapSystemMessage(new SystemMessage($systemPrompt))] : [],
            array_map(
                fn (Message $message): array => self::mapMessage($message),
                array_filter($messages, fn (Message $message): bool => $message instanceof SystemMessage)
            )
        ));
    }

    /**
     * @return array<string, mixed>
     */
    protected static function mapMessage(Message $message): array
    {
        return match ($message::class) {
            UserMessage::class => self::mapUserMessage($message),
            AssistantMessage::class => self::mapAssistantMessage($message),
            ToolResultMessage::class => self::mapToolResultMessage($message),
            SystemMessage::class => self::mapSystemMessage($message),
            default => throw new Exception('Could not map message type '.$message::class),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected static function mapSystemMessage(SystemMessage $systemMessage): array
    {
        $cacheType = data_get($systemMessage->providerMeta(Provider::Anthropic), 'cacheType', null);

        return array_filter([
            'type' => 'text',
            'text' => $systemMessage->content,
            'cache_control' => $cacheType ? ['type' => $cacheType instanceof UnitEnum ? $cacheType->value : $cacheType] : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function mapToolResultMessage(ToolResultMessage $message): array
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
     * @return array<string, mixed>
     */
    protected static function mapUserMessage(UserMessage $message): array
    {
        $cacheType = data_get($message->providerMeta(Provider::Anthropic), 'cacheType', null);
        $cache_control = $cacheType ? ['type' => $cacheType instanceof UnitEnum ? $cacheType->value : $cacheType] : null;

        return [
            'role' => 'user',
            'content' => [
                array_filter([
                    'type' => 'text',
                    'text' => $message->text(),
                    'cache_control' => $cache_control,
                ]),
                ...self::mapImageParts($message->images(), $cache_control),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function mapAssistantMessage(AssistantMessage $message): array
    {
        $content = [];

        if ($message->content !== '' && $message->content !== '0') {
            $cacheType = data_get($message->providerMeta(Provider::Anthropic), 'cacheType', null);

            $content[] = array_filter([
                'type' => 'text',
                'text' => $message->content,
                'cache_control' => $cacheType ? ['type' => $cacheType instanceof UnitEnum ? $cacheType->value : $cacheType] : null,
            ]);
        }

        $toolCalls = $message->toolCalls
            ? array_map(fn (ToolCall $toolCall): array => [
                'type' => 'tool_use',
                'id' => $toolCall->id,
                'name' => $toolCall->name,
                'input' => $toolCall->arguments(),
            ], $message->toolCalls)
            : [];

        return [
            'role' => 'assistant',
            'content' => array_merge($content, $toolCalls),
        ];
    }

    /**
     * @param  Image[]  $parts
     * @param  array<string, mixed>|null  $cache_control
     * @return array<string, mixed>
     */
    protected static function mapImageParts(array $parts, ?array $cache_control = null): array
    {
        return array_map(function (Image $image) use ($cache_control): array {
            if ($image->isUrl()) {
                throw new InvalidArgumentException('URL image type is not supported by Anthropic');
            }

            return array_filter([
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $image->mimeType,
                    'data' => $image->image,
                ],
                'cache_control' => $cache_control,
            ]);
        }, $parts);
    }
}
