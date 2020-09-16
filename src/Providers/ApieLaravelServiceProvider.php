<?php
namespace W2w\Laravel\Apie\Providers;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Http\Middleware\FrameGuard;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\ServerRequestInterface;
use W2w\Laravel\Apie\Console\DumpOpenApiSpecCommand;
use W2w\Laravel\Apie\Contracts\ApieMiddlewareBridgeContract;
use W2w\Laravel\Apie\Controllers\SwaggerUiController;
use W2w\Laravel\Apie\Middleware\Bridge\HeaderMapper;
use W2w\Laravel\Apie\Middleware\Bridge\MiddlewareToStatusCodeMapper;
use W2w\Laravel\Apie\Middleware\Bridge\ThrottleRequestMapper;
use W2w\Laravel\Apie\Services\ApieContext;
use W2w\Laravel\Apie\Services\ApieRouteLoader;
use W2w\Laravel\Apie\Services\LaravelRouteLoader;
use W2w\Laravel\Apie\Services\RequestToFacadeResponseConverter;
use W2w\Laravel\Apie\Services\RouteLoaderInterface;
use W2w\Lib\Apie\Core\Models\ApiResourceFacadeResponse;
use W2w\Lib\Apie\OpenApiSchema\Factories\SchemaFactory;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSchemaGenerator;

/**
 * Service provider for Apie to link to Laravel (and that do not work in Lumen)
 */
class ApieLaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $config = $this->app->get('apie.config');

        if ($config['bind-api-resource-facade-response']) {
            foreach ($config['resources'] as $resourceClass) {
                $this->registerResourceClass($resourceClass);
            }
        }

        $this->app->bind(RouteLoaderInterface::class, LaravelRouteLoader::class);
        resolve(ApieRouteLoader::class)->renderRoutes();
    }

    public function register()
    {
        // fix for https://github.com/laravel/framework/issues/30415
        $this->app->extend(
            ServerRequestInterface::class,
            function (ServerRequestInterface $psrRequest) {
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

        $this->app->bind(RequestToFacadeResponseConverter::class);

        $this->app->bind(
            SwaggerUiController::class,
            function () {
                $urlGenerator = $this->app->get(UrlGenerator::class);
                return new SwaggerUiController(
                    $this->app->get(ApieContext::class),
                    $urlGenerator,
                    __DIR__ . '/../../resources/open-api.html'
                );
            }
        );

        $this->app->bind(ApiResourceFacadeResponse::class, function () {
            /** @var ServerRequestInterface $request */
            $request = $this->app->get(ServerRequestInterface::class);

            /** @var RequestToFacadeResponseConverter $converter */
            $converter = $this->app->get(RequestToFacadeResponseConverter::class);

            return $converter->convertUnknownResourceClassToResponse($request);
        });

        $this->registerMiddlewareBridges();

        if ($this->app->runningInConsole()) {
            $this->commands([DumpOpenApiSpecCommand::class]);
        }
    }

    private function registerMiddlewareBridges()
    {
        $this->app->bind(ThrottleRequestMapper::class);
        $this->app->bind(HeaderMapper::class, function (Application $app) {
            return new HeaderMapper([
                SetCacheHeaders::class => [
                    'etag',
                    SchemaFactory::createStringSchema('md5', md5(__CLASS__)),
                    'etag cache header',
                ],
                FrameGuard::class => [
                    'x-Frame-Options',
                    SchemaFactory::createStringSchema(null, 'SAMEORIGIN'),
                    'Adds IFRAME protection'
                ],
            ]);
        });
        $this->app->bind(MiddlewareToStatusCodeMapper::class, function (Application $app) {
            return new MiddlewareToStatusCodeMapper(
                $app->get(OpenApiSchemaGenerator::class),
                [
                    401 => [Authenticate::class, 'Unauthenticated', 'User is not logged in'],
                    403 => [Authorize::class, 'Unauthorized', 'Invalid action for logged in user'],
                    503 => [CheckForMaintenanceMode::class, 'Maintenance', 'Bad gateway or application in maintenance mode'],
                ]
            );
        });
        $this->app->tag([HeaderMapper::class, ThrottleRequestMapper::class, MiddlewareToStatusCodeMapper::class], [ApieMiddlewareBridgeContract::class]);
    }

    public function registerResourceClass(string $resourceClass)
    {
        $this->app->bind($resourceClass, function () use ($resourceClass) {
            /** @var ServerRequestInterface $request */
            $request = $this->app->get(ServerRequestInterface::class);

            /** @var RequestToFacadeResponseConverter $converter */
            $converter = $this->app->get(RequestToFacadeResponseConverter::class);

            return $converter->convertRequestToResponse($resourceClass, $request)->getResource();
        });
    }
}
