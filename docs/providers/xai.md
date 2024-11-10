# xAI
## Configuration

```php
'xai' => [
    'api_key' => env('XAI_API_KEY', ''),
    'url' => env('XAI_URL', 'https://api.x.ai/v1'),
],
```

## Limitations
### Image Support

XAI does not support image inputs.

```php
// This is invalid
$message = new UserMessage(
    "What's in this image?",
    [Image::fromPath('/path/to/image.jpg')]
);
```
