<?php

use Illuminate\Support\Facades\Route;
use SAHM\ImageOptimizer\Http\Controllers\ImageUploadController;

Route::prefix(config('image-optimizer.routes.prefix', 'api/images'))
    ->middleware(config('image-optimizer.routes.middleware', ['api']))
    ->group(function () {
        Route::post('/', [ImageUploadController::class, 'upload'])->name('image-optimizer.upload');
        Route::get('/{hash}', [ImageUploadController::class, 'show'])->name('image-optimizer.show');
        Route::delete('/{hash}', [ImageUploadController::class, 'destroy'])->name('image-optimizer.destroy');
        Route::get('/system/info', [ImageUploadController::class, 'info'])->name('image-optimizer.info');
    });
