<?php
namespace W2w\Laravel\Apie\Services;

class LaravelRouteLoader implements RouteLoaderInterface
{
    public static function loadRestApiRoutes(): void
    {
        require __DIR__ . '/../../config/routes.php';
    }

    public static function loadOpenApiRoutes(): void
    {
        require __DIR__ . '/../../config/routes-openapi.php';
    }
}
