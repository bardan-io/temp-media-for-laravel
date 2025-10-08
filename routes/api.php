<?php

declare(strict_types=1);

use BardanIO\TempMedia\Http\Controllers\TempMediaController;
use Illuminate\Support\Facades\Route;

$routeConfig = config('temp-media.routes', []);

Route::group([
    'prefix' => $routeConfig['prefix'] ?? 'api/temp-media',
    'middleware' => $routeConfig['middleware'] ?? ['api'],
    'as' => $routeConfig['name_prefix'] ?? 'temp-media.',
], function () {
    Route::post('/', [TempMediaController::class, 'upload'])->name('upload');
    Route::get('/{id}', [TempMediaController::class, 'show'])->name('show');
    Route::delete('/{id}', [TempMediaController::class, 'destroy'])->name('destroy');
    Route::post('/validate', [TempMediaController::class, 'validate'])->name('validate');
});
