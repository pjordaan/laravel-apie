<?php
use W2w\Lib\Apie\Controllers\DocsController;
use W2w\Lib\Apie\Controllers\PostController;
use W2w\Lib\Apie\Controllers\PutController;
use W2w\Lib\Apie\Controllers\GetAllController;
use W2w\Lib\Apie\Controllers\GetController;
use W2w\Lib\Apie\Controllers\DeleteController;

$router = app('router');

$router->group(['prefix' => app('apie.config')['api-url']], function () use ($router) {
    $router->get('/doc.json', ['as' => 'apie.docs', 'uses' => DocsController::class]);
    $router->post('/{resource}/', ['as' => 'apie.post', 'uses' => PostController::class]);
    $router->put('/{resource}/{id}', ['as' => 'apie.put', 'uses' => PutController::class]);
    $router->get('/{resource}/', ['as' => 'apie.all', 'uses' => GetAllController::class]);
    $router->get('/{resource}/{id}', ['as' => 'apie.get', 'uses' => GetController::class]);
    $router->delete('/{resource}/{id}', ['as' => 'apie.delete', 'uses' => DeleteController::class]);
});

