<?php
use W2w\Lib\Apie\Controllers\DocsController;
use W2w\Lib\Apie\Controllers\PostController;
use W2w\Lib\Apie\Controllers\PutController;
use W2w\Lib\Apie\Controllers\GetAllController;
use W2w\Lib\Apie\Controllers\GetController;
use W2w\Lib\Apie\Controllers\DeleteController;
Route::name('apie.')->group(function () {
    Route::prefix(resolve('apie.config')['api-url'])->group(function () {
        Route::get('/doc.json', DocsController::class)->name('docs');
        Route::post('/{resource}/', PostController::class)->name('post');
        Route::put('/{resource}/{id}', PutController::class)->name('put');
        Route::get('/{resource}/', GetAllController::class)->name('all');
        Route::get('/{resource}/{id}', GetController::class)->name('get');
        Route::delete('/{resource}/{id}', DeleteController::class)->name('delete');
    });
});
