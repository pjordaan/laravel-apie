<?php

namespace W2w\Laravel\Apie\Services;

use Closure;

/**
 * Interface that will load the routes.
 */
interface RouteLoaderInterface
{
    public function addDocUrl(array $context);

    public function addResourceUrl(array $context);

    public function addSwaggerUiUrl(array $context);

    public function context(array $context, string $prefix, array $middleware, Closure $closure);
}
