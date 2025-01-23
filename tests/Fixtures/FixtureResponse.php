<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Http;

class FixtureResponse
{
    public static function fromFile(
        string $filePath,
        int $statusCode = 200,
        $headers = []
    ): PromiseInterface {
        return Http::response(
            file_get_contents(static::filePath($filePath)),
            $statusCode,
            $headers,
        );
    }

    public static function filePath(string $filePath): string
    {
        return sprintf('%s/%s', __DIR__, $filePath);
    }

    public static function recordResponses(string $requestPath, string $name): void
    {
        $iterator = 0;

        Http::globalResponseMiddleware(function ($response) use ($name, &$iterator) {
            $iterator++;

            $path = static::filePath("{$name}-{$iterator}.json");

            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), recursive: true);
            }

            file_put_contents(
                $path,
                (string) $response->getBody()
            );

            return $response;
        });
    }

    public static function fakeResponseSequence(string $requestPath, string $name, array $headers = []): void
    {
        $responses = collect(scandir(dirname(static::filePath($name))))
            ->filter(function (string $file) use ($name): int|false {
                $pathInfo = pathinfo($name);
                $filename = $pathInfo['filename'];

                return preg_match('/^'.preg_quote($filename, '/').'-\d+/', $file);
            })
            ->map(fn ($filename): string => dirname(static::filePath($name)).'/'.$filename)
            ->map(fn ($filePath) => Http::response(
                file_get_contents($filePath),
                200,
                $headers
            ));

        Http::fake([
            $requestPath => Http::sequence($responses->toArray()),
        ])->preventStrayRequests();
    }
}
