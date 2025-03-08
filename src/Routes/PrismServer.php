<?php

use Illuminate\Support\Facades\Route;
use PrismPHP\Prism\Http\Controllers\PrismChatController;
use PrismPHP\Prism\Http\Controllers\PrismModelController;

Route::prefix('/prism/openai/v1')
    ->group(function (): void {
        Route::post('/chat/completions', PrismChatController::class);
        Route::get('/models', PrismModelController::class);
    });
