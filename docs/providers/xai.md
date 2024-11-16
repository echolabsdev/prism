# xAI
## Configuration

```php
'xai' => [
    'api_key' => env('XAI_API_KEY', ''),
    'url' => env('XAI_URL', 'https://api.x.ai/v1'),
],
```

## Limitations
### Tool Choice

xAI does not support `->withToolChoice`.

### Image Support

xAI does not support image inputs.
