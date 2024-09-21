<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Http\Controllers;

use EchoLabs\Prism\Facades\PrismServer;
use Illuminate\Http\JsonResponse;

class PrismModelController
{
    public function __invoke(): JsonResponse
    {
        $prisms = PrismServer::prisms()
            ->map(fn (array $model): array => [
                'id' => $model['name'],
                'object' => 'model',
            ]);

        return response()->json(
            [
                'object' => 'list',
                'data' => $prisms->toArray(),
            ]
        );
    }
}
