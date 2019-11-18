<?php
namespace W2w\Laravel\Apie\Providers;

use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\ServerRequestInterface;
use W2w\Laravel\Apie\Controllers\SwaggerUiController;

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
        if ($config['swagger-ui-test-page']) {
            include __DIR__ . '/../../config/routes-lumen-openapi.php';
        }
        include __DIR__ . '/../../config/routes-lumen.php';
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
