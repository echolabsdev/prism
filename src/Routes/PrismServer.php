<?php

use EchoLabs\Prism\Http\Controllers\PrismChatController;
use EchoLabs\Prism\Http\Controllers\PrismModelController;
use Illuminate\Support\Facades\Route;

Route::prefix('/prism/openai/v1')
    ->group(function (): void {
        Route::post('/chat/completions', PrismChatController::class);
        Route::get('/models', PrismModelController::class);
    });
