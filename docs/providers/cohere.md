# Cohere

## Configuration

```php
'cohere' => [
    'api_key' => env('COHERE_API_KEY', ''),
    'url' => env('COHERE_URL', 'https://api.cohere.com/v2'),
    'embed' => [
        'input' => env('COHERE_EMBED_INPUT', 'classification'),
        'output' => env('COHERE_EMBED_OUTPUT', 'float'),
    ]
],
```

## Provider-specific Settings

You may tweak the embedding configuration to your needs by altering the input and output values and structure.
Check the [docs](https://docs.cohere.com/reference/embed) to find more information about `input_type` (input) and `embedding_types` (output).
Only one `embedding_types` is currently supported.

## Limitations

This Cohere implementation does only support API v2.
For differences please check out the [documentation](https://docs.cohere.com/docs/migrating-v1-to-v2)