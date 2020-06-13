<?php
namespace W2w\Laravel\Apie\Services;

use Closure;
use W2w\Laravel\Apie\Controllers\SwaggerUiController;
use W2w\Lib\Apie\Controllers\DeleteController;
use W2w\Lib\Apie\Controllers\DocsController;
use W2w\Lib\Apie\Controllers\DocsYamlController;
use W2w\Lib\Apie\Controllers\GetAllController;
use W2w\Lib\Apie\Controllers\GetController;
use W2w\Lib\Apie\Controllers\PostController;
use W2w\Lib\Apie\Controllers\PutController;

class LumenRouteLoader implements RouteLoaderInterface
{
    public function addDocUrl(array $context)
    {
        if (empty($context)) {
            $contextString = '';
        } else {
            $contextString = implode('.', $context) . '.';
        }
        $router = app('router');
        $router->get('/doc.json', ['as' => 'apie.' . $contextString . 'docs', 'uses' => DocsController::class]);
        $router->get('/doc.yml', ['as' => 'apie.' . $contextString . 'docsyaml', 'uses' => DocsYamlController::class]);
    }

    public function addResourceUrl(array $context)
    {
        if (empty($context)) {
            $contextString = '';
        } else {
            $contextString = implode('.', $context) . '.';
        }
        $router = app('router');
        $router->post('/{resource}/', ['as' => 'apie.' . $contextString . 'post', 'uses' => PostController::class]);
        $router->put('/{resource}/{id}', ['as' => 'apie.' . $contextString . 'put', 'uses' => PutController::class]);
        $router->get('/{resource}/', ['as' => 'apie.' . $contextString . 'all', 'uses' => GetAllController::class]);
        $router->get('/{resource}/{id}', ['as' => 'apie.' . $contextString . 'get', 'uses' => GetController::class]);
        $router->delete('/{resource}/{id}', ['as' => 'apie.' . $contextString . 'delete', 'uses' => DeleteController::class]);
    }

    public function addSwaggerUiUrl(array $context)
    {
        if (empty($context)) {
            $contextString = '';
        } else {
            $contextString = implode('.', $context) . '.';
        }
        $router = app('router');
        $router->get(
            '',
            [
                'as' => 'apie.' . $contextString . 'swagger-ui',
                'uses' => SwaggerUiController::class
            ]
        );
    }

    public function context(array $context, string $prefix, array $middleware, Closure $closure)
    {
        $router = app('router');
        $router->group(
            ['parameters' => ['context' => $context], 'prefix' => $prefix, 'middleware' => $middleware],
            function () use ($closure) {
                $closure();
            }
        );
    }
}
