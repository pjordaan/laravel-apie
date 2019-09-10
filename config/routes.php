<?php
use W2w\Lib\Apie\Controllers\DocsController;
use W2w\Lib\Apie\Controllers\PostController;
use W2w\Lib\Apie\Controllers\PutController;
use W2w\Lib\Apie\Controllers\GetAllController;
use W2w\Lib\Apie\Controllers\GetController;
use W2w\Lib\Apie\Controllers\DeleteController;
Route::name('apie.')->group(function () {
    Route::prefix(config('api-resource.api-url'))->group(function () {
        Route::get('/doc.json', DocsController::class)->name('apie.docs');
        Route::post('/{resource}/', PostController::class);
        Route::put('/{resource}/{id}', PutController::class);
        Route::get('/{resource}/', GetAllController::class);
        Route::get('/{resource}/{id}', GetController::class);
        Route::delete('/{resource}/{id}', DeleteController::class);
    });
});
