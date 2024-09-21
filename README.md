![](docs/images/prism-banner.webp)

# Prism

Prism is a powerful Laravel package for integrating Large Language Models (LLMs) into your applications. It provides a fluent interface for generating text, handling multi-step conversations, and utilizing tools with various AI providers. This way, you can focus on developing outstanding AI
  applications for your users without getting lost in the technical intricacies.

Official documentation can be found at [prism.echolabs.dev](https://prism.echolabs.dev).

## Installation

### Step 1: Composer Installation

First, let's add Prism to your project using Composer. Open your terminal, navigate to your project directory, and run:

```shell
composer require echolabsdev/prism
```

This command will download Prism and its dependencies into your project.

### Step 2: Publish the Configuration

Prism comes with a configuration file that you'll want to customize. Publish it to your config directory by running:

```shell
php artisan vendor:publish --tag=prism-config
```

This will create a new file at `config/prism.php`. We'll explore how to configure Prism in the next section.

## Usage

```php
<?php

$response = Prism::using('anthropic', 'claude-3-5-sonnet-20240620')
    ->generateText()
    ->withSystemMessage(view('prompts.nyx'))
    ->withPrompt('Explain quantum computing to a 5-year-old.')();

echo $response->text;
```

## Authors

This library is created by [TJ Miller](https://tjmiller.me) with contributions from the [Open Source Community](https://github.com/vercel/ai/graphs/contributors).

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

