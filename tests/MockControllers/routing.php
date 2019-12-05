<?php

use Illuminate\Support\Facades\Route;
use W2w\Laravel\Apie\Tests\MockControllers\MockController;

Route::get('/test-facade-response/{resource}', [MockController::class, 'testApiResourceFacadeResponseList']);
Route::get('/test-facade-response/{resource}/{id}', [MockController::class, 'testApiResourceFacadeResponse']);

Route::get('/test-resource-typehint/{id}', [MockController::class, 'testResourceTypehint']);
