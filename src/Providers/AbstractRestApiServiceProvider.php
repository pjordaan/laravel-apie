<?php


namespace W2w\Laravel\Apie\Providers;

use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\ServerRequestInterface;
use W2w\Laravel\Apie\Services\RequestToFacadeResponseConverter;

/**
 * Service provider that allows you to add a REST api from a service provider.
 */
abstract class AbstractRestApiServiceProvider extends ServiceProvider
{
    abstract protected function getApiName(): string;

    abstract protected function getApiConfig(): array;

    public function register()
    {
        $apiName = $this->getApiName();
        $apiConfig = $this->getApiConfig();
        $this->app->get('config')->set('apie.contexts.' . $apiName, $apiConfig);
        if ($apiConfig['bind-api-resource-facade-response'] ?? true) {
            foreach ($apiConfig['resources'] as $resourceClass) {
                $this->registerResourceClass($apiName, $resourceClass);
            }
        }
    }

    private function registerResourceClass(string $apiName, string $resourceClass)
    {
        $this->app->bind($resourceClass, function () use ($resourceClass, $apiName) {
            /** @var ServerRequestInterface $request */
            $request = $this->app->get(ServerRequestInterface::class);

            /** @var RequestToFacadeResponseConverter $converter */
            $converter = $this->app->get(RequestToFacadeResponseConverter::class);

            return $converter->convertRequestToResponse($resourceClass, $request)->getResource();
        });
    }
}
