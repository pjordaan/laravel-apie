<?php
namespace W2w\Laravel\Apie\Services;

use Closure;
use Illuminate\Support\Facades\Route;
use W2w\Laravel\Apie\Controllers\SwaggerUiController;
use W2w\Lib\Apie\Controllers\DeleteController;
use W2w\Lib\Apie\Controllers\DocsController;
use W2w\Lib\Apie\Controllers\DocsYamlController;
use W2w\Lib\Apie\Controllers\GetAllController;
use W2w\Lib\Apie\Controllers\GetController;
use W2w\Lib\Apie\Controllers\PostController;
use W2w\Lib\Apie\Controllers\PutController;
use W2w\Lib\Apie\Controllers\SubActionController;

class LaravelRouteLoader implements RouteLoaderInterface
{
    public function addSwaggerUiUrl(array $context)
    {
        if (empty($context)) {
            $contextString = '';
        } else {
            $contextString = implode('.', $context) . '.';
        }
        Route::get('', SwaggerUiController::class)->name('apie.' . $contextString . 'swagger-ui')->defaults('context', $context);
    }

    public function addDocUrl(array $context)
    {
        if (empty($context)) {
            $contextString = '';
        } else {
            $contextString = implode('.', $context) . '.';
        }

        Route::get('/doc.json', DocsController::class)->name('apie.' . $contextString . 'docs')->defaults('context', $context);
        Route::get('/doc.yml', DocsYamlController::class)->name('apie.' . $contextString . 'docsyaml')->defaults('context', $context);
    }

    public function addResourceUrl(array $context)
    {
        if (empty($context)) {
            $contextString = '';
        } else {
            $contextString = implode('.', $context) . '.';
        }

        Route::post('/{resource}/{id}/{subaction}', SubActionController::class)->name('apie.' . $contextString . 'post.subaction')->defaults('context', $context);
        Route::post('/{resource}/', PostController::class)->name('apie.' . $contextString . 'post')->defaults('context', $context);
        Route::put('/{resource}/{id}', PutController::class)->name('apie.' . $contextString . 'put')->defaults('context', $context);
        Route::get('/{resource}/', GetAllController::class)->name('apie.' . $contextString . 'all')->defaults('context', $context);
        Route::get('/{resource}/{id}', GetController::class)->name('apie.' . $contextString . 'get')->defaults('context', $context);
        Route::delete('/{resource}/{id}', DeleteController::class)->name('apie.' . $contextString . 'delete')->defaults('context', $context);
    }

    public function context(array $context, string $prefix, array $middleware, Closure $closure)
    {
        Route::group(
            [
                'prefix' => $prefix,
                'middleware' => $middleware
            ],
            function () use ($closure, &$context) {
                $closure($context);
            }
        );
    }
}
