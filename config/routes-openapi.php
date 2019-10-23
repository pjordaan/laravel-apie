<?php
use W2w\Laravel\Apie\Controllers\SwaggerUiController;

$apieConfig = resolve('apie.config');

Route::group(['middleware' => $apieConfig['swagger-ui-test-page-middleware']], function() use (&$apieConfig) {
    Route::get($apieConfig['swagger-ui-test-page'], SwaggerUiController::class)->name('apie.swagger-ui');
});
