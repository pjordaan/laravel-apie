<?php
use W2w\Laravel\Apie\Controllers\SwaggerUiController;

Route::get(config('api-resource.swagger-ui-test-page', SwaggerUiController::class))->name('apie.swagger-ui');
