# Gemini
## Configuration

```php
'gemini' => [
    'api_key' => env('GEMINI_API_KEY', ''),
    'url' => env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta/models'),
],
```

### Structured Output

- Gemini support for Structured Output is using JSON mode, by automatically appending instructions to your prompt that guide the model to output valid JSON matching your schema


### Limitations

- The Model Structured Output is still not supported.