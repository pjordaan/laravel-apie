<?php
namespace W2w\Laravel\Apie\Providers;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\ServerRequestInterface;
use W2w\Laravel\Apie\Controllers\SwaggerUiController;

/**
 * Service provider for Apie to link to Laravel (and that do not work in Lumen)
 */
class ApieLaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $config = $this->app->get('apie.config');
        if ($config['disable-routes']) {
            return;
        }
        if ($config['swagger-ui-test-page']) {
            $this->loadRoutesFrom(__DIR__ . '/../../config/routes-openapi.php');
        }
        $this->loadRoutesFrom(__DIR__ . '/../../config/routes.php');
    }

    public function register()
    {
        // fix for https://github.com/laravel/framework/issues/30415
        $this->app->extend(
            ServerRequestInterface::class, function (ServerRequestInterface $psrRequest) {
                $route = $this->app->make('request')->route();
                if ($route) {
                    $parameters = $route->parameters();
                    foreach ($parameters as $key => $value) {
                        $psrRequest = $psrRequest->withAttribute($key, $value);
                    }
                }
                return $psrRequest;
            }
        );

        $this->app->bind(
            SwaggerUiController::class, function () {
                $urlGenerator = $this->app->get(UrlGenerator::class);
                return new SwaggerUiController($urlGenerator, __DIR__ . '/../../resources/open-api.html');
            }
        );
    }
}
