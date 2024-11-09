# Anthropic
## Configuration

```php
'anthropic' => [
    'api_key' => env('ANTHROPIC_API_KEY', ''),
    'version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
]
```

## Considerations

- Message order matters. Anthropic is strict about the message order being:

1. `UserMessage`
2. `AssistantMessage`
3. `UserMessage`

## Limitations

- Does not support the `SystemMessage` message type, we automatically convert `SystemMessage` to `UserMessage`.
- Does not support `Image::fromURL`
