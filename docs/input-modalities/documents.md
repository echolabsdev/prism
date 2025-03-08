# Documents

Prism currently supports documents with Gemini and Anthropic.

## Supported file types

Different providers support different document types.

At the time of writing:
- Anthropic supports 
    - pdf (application/pdf) 
    - txt (text/plain)
    - md (text/md)
- Gemini supports:
    - pdf (application/pdf)
    - javascript (text/javascript)
    - python (text/x-python)
    - txt (text/plain)
    - html (text/html)
    - css (text/css)
    - md (text/md)
    - csv (text/csv)
    - xml (text/xml)
    - rtf (text/rtf)

All of these formats should work with Prism.

## Getting started

To add an image to your message, add a `Document` value object to the `additionalContent` property:

```php
use PrismPHP\Enums\Provider;
use PrismPHP\Prism\Prism;
use PrismPHP\Prism\ValueObjects\Messages\UserMessage;
use PrismPHP\Prism\ValueObjects\Messages\Support\Document;

Prism::text()
    ->using(Provider::Anthropic, 'claude-3-5-sonnet-20241022')
    ->withMessages([
        // From base64
        new UserMessage('Here is the document from base64', [
            Document::fromBase64(base64_encode(file_get_contents('tests/Fixtures/test-pdf.pdf')), 'application/pdf'),
        ]),
        // Or from a path
        new UserMessage('Here is the document from a local path', [
            Document::fromPath('tests/Fixtures/test-pdf.pdf'),
        ]),
        // Or from a text string
        new UserMessage('Here is the document from a text string (e.g. from your database)', [
            Document::fromText('Hello world!'),
        ]),
    ])
    ->generate();

```