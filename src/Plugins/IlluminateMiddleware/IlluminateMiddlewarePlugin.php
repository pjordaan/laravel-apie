<?php

namespace W2w\Laravel\Apie\Plugins\IlluminateMiddleware;

use erasys\OpenApi\Spec\v3\Components;
use erasys\OpenApi\Spec\v3\Document;
use erasys\OpenApi\Spec\v3\Operation;
use Illuminate\Foundation\Http\Kernel;
use W2w\Laravel\Apie\Contracts\ApieMiddlewareBridgeContract;
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
     * @var Kernel
     */
    private $kernel;

    public function __construct(ApieContext $apieContext, Kernel $kernel)
    {
        $this->apieContext = $apieContext;
        $this->kernel = $kernel;
    }

    public function onOpenApiDocGenerated(Document $document): Document
    {
        if (!$document->components) {
            $document->components = new Components([]);
        }
        $components = $document->components;
        $middleware = $this->apieContext->getActiveContext()->getConfig('apie-middleware');
        $resolver = new MiddlewareResolver\MiddlewareResolver($this->kernel, $this->kernel->getApplication());
        $middlewareClasses = iterator_to_array($resolver->resolveMiddleware($middleware));
        foreach ($document->paths as $key => $pathItem) {
            $this->patch($pathItem->get, $components, $middlewareClasses);
        }
    }

    private function patch(?Operation $operation, Components $components, array $middlewareClasses)
    {
        if (null == $operation) {
            return;
        }
        foreach ($this->kernel->getApplication()->tagged([ApieMiddlewareBridgeContract::class]) as $apieMiddleware) {
            if (!($apieMiddleware instanceof ApieMiddlewareBridgeContract)) {
                throw new InvalidClassTypeException(get_class($apieMiddleware), ApieMiddlewareBridgeContract::class);
            }
            foreach ($middlewareClasses as $middlewareClass) {
                $apieMiddleware->patch($operation, $components, $middlewareClass);
            }
        }
    }
}
