<?php
use W2w\Lib\Apie\Controllers\DocsController;
use W2w\Lib\Apie\Controllers\PostController;
use W2w\Lib\Apie\Controllers\PutController;
use W2w\Lib\Apie\Controllers\GetAllController;
use W2w\Lib\Apie\Controllers\GetController;
use W2w\Lib\Apie\Controllers\DeleteController;

$apieConfig = resolve('apie.config');

Route::group(
    [
        'prefix' => $apieConfig['api-url'],
        'middleware' => $apieConfig['apie-middleware']
    ],
    function () {
        Route::get('/doc.json', DocsController::class)->name('apie.docs');
        Route::post('/{resource}/', PostController::class)->name('apie.post');
        Route::put('/{resource}/{id}', PutController::class)->name('apie.put');
        Route::get('/{resource}/', GetAllController::class)->name('apie.all');
        Route::get('/{resource}/{id}', GetController::class)->name('apie.get');
        Route::delete('/{resource}/{id}', DeleteController::class)->name('apie.delete');
    }
);
