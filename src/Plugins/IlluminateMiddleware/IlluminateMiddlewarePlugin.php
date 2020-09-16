<?php

namespace W2w\Laravel\Apie\Plugins\IlluminateMiddleware;

use erasys\OpenApi\Spec\v3\Components;
use erasys\OpenApi\Spec\v3\Document;
use erasys\OpenApi\Spec\v3\Operation;
use Illuminate\Container\Container;
use W2w\Laravel\Apie\Contracts\ApieMiddlewareBridgeContract;
use W2w\Laravel\Apie\Plugins\IlluminateMiddleware\MiddlewareResolver\MiddlewareResolver;
use W2w\Laravel\Apie\Services\ApieContext;
use W2w\Lib\Apie\Exceptions\InvalidClassTypeException;
use W2w\Lib\Apie\PluginInterfaces\OpenApiEventProviderInterface;

/**
 * Parses the configured middleware to find out which responses given by Laravel/Lumen are possible.
 */
class IlluminateMiddlewarePlugin implements OpenApiEventProviderInterface
{
    /**
     * @var ApieContext
     */
    private $apieContext;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var MiddlewareResolver
     */
    private $resolver;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    private function getMiddlewareResolver(): MiddlewareResolver
    {
        if (!$this->resolver) {
            $this->resolver = new MiddlewareResolver($this->container);
        }
        return $this->resolver;
    }

    public function onOpenApiDocGenerated(Document $document): Document
    {
        if (!$document->components) {
            $document->components = new Components([]);
        }
        $components = $document->components;
        $middleware = $this->container->make(ApieContext::class)->getActiveContext()->getConfig('apie-middleware');
        $resolver = $this->getMiddlewareResolver();
        $middlewareClasses = iterator_to_array($resolver->resolveMiddleware($middleware));
        foreach ($document->paths as $key => $pathItem) {
            $this->patch($pathItem->get, $components, $middlewareClasses);
            $this->patch($pathItem->post, $components, $middlewareClasses);
            $this->patch($pathItem->put, $components, $middlewareClasses);
            $this->patch($pathItem->patch, $components, $middlewareClasses);
            $this->patch($pathItem->delete, $components, $middlewareClasses);
            $this->patch($pathItem->head, $components, $middlewareClasses);
            $this->patch($pathItem->options, $components, $middlewareClasses);
        }
        return $document;
    }

    private function patch(?Operation $operation, Components $components, array $middlewareClasses)
    {
        if (null == $operation) {
            return;
        }
        foreach ($this->container->tagged(ApieMiddlewareBridgeContract::class) as $apieMiddleware) {
            if (!($apieMiddleware instanceof ApieMiddlewareBridgeContract)) {
                throw new InvalidClassTypeException(get_class($apieMiddleware), ApieMiddlewareBridgeContract::class);
            }
            foreach ($middlewareClasses as $middlewareClass) {
                $apieMiddleware->patch($operation, $components, $middlewareClass);
            }
        }
    }
}
