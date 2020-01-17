<?php
namespace W2w\Laravel\Apie\Providers;

use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\ServerRequestInterface;
use W2w\Laravel\Apie\Controllers\SwaggerUiController;
use W2w\Laravel\Apie\Services\LumenRouteLoader;
use W2w\Laravel\Apie\Services\RouteLoaderInterface;

/**
 * Service provider for Apie to link to Laravel (and that do not work in Laravel)
 */
class ApieLumenServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $config = $this->app->get('apie.config');
        if ($config['disable-routes']) {
            return;
        }
        $this->app->bind(RouteLoaderInterface::class, LumenRouteLoader::class);
        if ($config['swagger-ui-test-page']) {
            LumenRouteLoader::loadOpenApiRoutes();
        }
        LumenRouteLoader::loadRestApiRoutes();
    }

    public function register()
    {
        // fix for PSR requests in Lumen
        $this->app->extend(
            ServerRequestInterface::class, function (ServerRequestInterface $psrRequest) {
                $route = (array) $this->app->make('request')->route();
                if (is_array($route[2])) {
                    foreach ($route[2] as $key => $value) {
                        $psrRequest = $psrRequest->withAttribute($key, $value);
                    }
                }
                return $psrRequest;
            }
        );

        $this->app->bind(
            SwaggerUiController::class, function () {
                $urlGenerator = new \Laravel\Lumen\Routing\UrlGenerator($this->app);
                return new SwaggerUiController($urlGenerator, __DIR__ . '/../../resources/open-api.html');
            }
        );
    }
}
