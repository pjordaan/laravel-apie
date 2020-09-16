<?php

namespace W2w\Laravel\Apie\Plugins\IlluminateMiddleware\MiddlewareResolver;

use Generator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\MiddlewareNameResolver;
use Illuminate\Routing\Router;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Hacky class that extracts the classes used in the kernel. The classes are used to add the default
 * responses available.
 */
class MiddlewareResolver
{
    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Helper method to get something hidden from the kernel.
     *
     * @param $propertyField
     * @return mixed
     */
    final protected function getHiddenPropertyValue($propertyField)
    {
        $method = new ReflectionProperty($this->container->make('router'), $propertyField);
        $method->setAccessible(true);
        return $method->getValue($this->container->make('router'));
    }

    /**
     * Returns mapping of identifiers with which middleware class.
     *
     * @return string[]
     */
    protected function getMiddlewareMapping(): array
    {
        return $this->getHiddenPropertyValue('middleware');
    }

    /**
     * Returns all middleware groups.
     *
     * @return string[][]
     */
    protected function getMiddlewareGroups(): array
    {
        return $this->getHiddenPropertyValue('middlewareGroups');
    }

    /**
     * Returns all classes that are being used as middleware.
     *
     * @param array $middlewares
     * @return Generator<string>
     */
    public function resolveMiddleware(array $middlewares): Generator
    {
        $map = $this->getMiddlewareMapping();
        $middlewareGroups = $this->getMiddlewareGroups();

        foreach ($middlewares as $middleware) {

            $resolvedMiddleware = MiddlewareNameResolver::resolve($middleware, $map, $middlewareGroups);
            if (is_string($resolvedMiddleware)) {
                $pos = strpos($resolvedMiddleware, ':');
                if ($pos !== false) {
                    $resolvedMiddleware = substr($resolvedMiddleware, 0, $pos);
                }
                if (class_exists($resolvedMiddleware)) {
                    yield $resolvedMiddleware;
                }
            }
        }
    }
}
