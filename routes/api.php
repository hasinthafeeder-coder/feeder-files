<?php

use App\Http\Controllers\Api\FileController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
    ]);
});

Route::prefix('files')
    ->middleware('file.api')
    ->group(function () {

        Route::get('/test', function () {
            return response()->json([
                'authenticated' => true,
            ]);
        });

        Route::post('/upload', [FileController::class, 'upload']);
    });
