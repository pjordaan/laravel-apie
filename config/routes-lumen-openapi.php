<?php
use W2w\Laravel\Apie\Controllers\SwaggerUiController;

$router = app('router');

$apieConfig = app('apie.config');

$router->get(
    $apieConfig['swagger-ui-test-page'],
    [
        'as' => 'apie.swagger-ui',
        'uses' => SwaggerUiController::class,
        'middleware' => $apieConfig['swagger-ui-test-page-middleware']
    ]
);
