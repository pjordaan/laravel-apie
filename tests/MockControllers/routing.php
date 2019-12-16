<?php

use Illuminate\Support\Facades\Route;
use W2w\Laravel\Apie\Tests\MockControllers\MockController;

Route::get(
    '/test-facade-response/{resource}',
    ['uses' => MockController::class . '@testApiResourceFacadeResponseList']
);
Route::get(
    '/test-facade-response/{resource}/{id}',
    ['uses' => MockController::class . '@testApiResourceFacadeResponse']
);

Route::get(
    '/test-resource-typehint/{id}',
    ['uses' => MockController::class . '@testResourceTypehint']
);
