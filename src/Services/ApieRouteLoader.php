<?php


namespace W2w\Laravel\Apie\Services;

use W2w\Laravel\Apie\Middleware\HandleAcceptLanguage;

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
            $middleware = $context->getConfig('apie-middleware');
            if ($context->getConfig('translations')) {
                array_unshift($middleware, HandleAcceptLanguage::class. ':' . implode(',', $context->getConfig('translations')));
            }
            $this->routeLoader->context(
                $context->getContextKey(),
                $context->getConfig('api-url'),
                $middleware,
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
