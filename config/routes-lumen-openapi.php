<?php
use W2w\Laravel\Apie\Controllers\SwaggerUiController;

$router = app('router');

$router->get(resolve('apie.config')['swagger-ui-test-page'], ['as' => 'apie.swagger-ui', 'uses' => SwaggerUiController::class]);
