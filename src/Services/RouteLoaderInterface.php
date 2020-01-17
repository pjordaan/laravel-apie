<?php


namespace W2w\Laravel\Apie\Services;

/**
 * Interface that will load the routes.
 */
interface RouteLoaderInterface
{
    public static function loadRestApiRoutes(): void;

    public static function loadOpenApiRoutes(): void;
}
