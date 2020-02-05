<?php


namespace W2w\Laravel\Apie\Services;

class ApieRouteLoader
{
    private $context;

    private $routeLoader;

    public function __construct(ApieContext $context, RouteLoaderInterface $routeLoader) {
        $this->context = $context;
        $this->routeLoader = $routeLoader;
    }

    public function renderRoutes()
    {
        $this->renderContext($this->context);
    }

    private function renderContext(ApieContext $context)
    {
        if (!$context->getConfig('disable-routes')) {
            $this->routeLoader->context(
                $context->getContextKey(),
                $context->getConfig('api-url'),
                $context->getConfig('swagger-ui-test-page-middleware'),
                function () use ($context) {
                    $this->routeLoader->addDocUrl($context->getContextKey());
                }
            );
            $this->routeLoader->context(
                $context->getContextKey(),
                $context->getConfig('api-url'),
                $context->getConfig('apie-middleware'),
                function () use ($context) {
                    $this->routeLoader->addResourceUrl($context->getContextKey());
                }
            );
            if ($context->getConfig('swagger-ui-test-page')) {
                $this->routeLoader->context(
                    $context->getContextKey(),
                    $context->getConfig('swagger-ui-test-page'),
                    $context->getConfig('swagger-ui-test-page-middleware'),
                    function () use ($context) {
                        $this->routeLoader->addSwaggerUiUrl($context->getContextKey());
                    }
                );
            }
        }
        foreach ($context->allContexts() as $childContext) {
            $this->renderContext($childContext);
        }
    }
}
