<?php
use W2w\Laravel\Apie\Controllers\SwaggerUiController;

Route::get(resolve('apie.config')['swagger-ui-test-page'], SwaggerUiController::class)->name('apie.swagger-ui');
