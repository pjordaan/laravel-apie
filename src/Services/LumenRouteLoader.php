<?php
namespace W2w\Laravel\Apie\Services;

class LumenRouteLoader implements RouteLoaderInterface
{
    public static function loadRestApiRoutes(): void
    {
        require __DIR__ . '/../../config/routes-lumen.php';
    }

    public static function loadOpenApiRoutes(): void
    {
        require __DIR__ . '/../../config/routes-lumen-openapi.php';
    }
}
