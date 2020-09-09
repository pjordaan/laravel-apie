<?php

namespace W2w\Laravel\Apie\Plugins\IlluminateMiddleware\MiddlewareResolver;

use Generator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\MiddlewareNameResolver;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Hacky class that extracts the classes used in the kernel. The classes are used to add the default
 * responses available.
 */
class MiddlewareResolver
{
    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @var Container
     */
    private $container;

    public function __construct(Kernel $kernel, Container $container)
    {
        $this->kernel = $kernel;
        $this->container = $container;
    }

    /**
     * Gets Kernel middleware that is always being executed.
     *
     * @return array
     */
    protected function getKernelMiddleware(): array
    {
        return $this->getHiddenPropertyValue('middleware');
    }

    /**
     * Runs the parseMiddleware from the kernel.
     *
     * @param string $middleware
     * @return mixed[]
     */
    protected function parseMiddleware(string $middleware): array
    {
        $method = new ReflectionMethod($this->kernel, 'parseMiddleware');
        $method->setAccessible(true);
        return $method->invoke($this->kernel, $middleware);
    }

    /**
     * Helper method to get something hidden from the kernel.
     *
     * @param $propertyField
     * @return mixed
     */
    final protected function getHiddenPropertyValue($propertyField)
    {
        $method = new ReflectionProperty($this->kernel, $propertyField);
        $method->setAccessible(true);
        return $method->getValue($this->kernel);
    }

    /**
     * Returns mapping of identifiers with which middleware class.
     *
     * @return string[]
     */
    protected function getMiddlewareMapping(): array
    {
        return $this->getHiddenPropertyValue('routeMiddleware');
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
     * @param array $middleware
     * @return Generator<string>
     */
    public function resolveMiddleware(array $middleware): Generator
    {
        $middlewares = array_merge($this->getKernelMiddleware(), $middleware);
        $map = $this->getMiddlewareMapping();
        $middlewareGroups = $this->getMiddlewareGroups();

        foreach ($middlewares as $middleware) {

            $resolvedMiddleware = MiddlewareNameResolver::resolve($middleware, $map, $middlewareGroups);
            // To avoid closures, etc.
            if (!is_string($resolvedMiddleware)) {
                continue;
            }
            [$name] = $this->parseMiddleware($resolvedMiddleware);
            if (class_exists($name)) {
                yield $name;
            }
        }
    }
}
