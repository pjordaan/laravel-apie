<?php


namespace W2w\Laravel\Apie\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider that allows you to add a REST api from a service provider.
 */
abstract class AbstractRestApiServiceProvider extends ServiceProvider
{
    abstract protected function getApiName(): string;

    abstract protected function getApiConfig(): array;

    public function register()
    {
        $apiConfig = $this->getApiConfig();
        $this->app->get('config')->set('apie.contexts.' . $this->getApiName(), $apiConfig);
        if ($apiConfig['bind-api-resource-facade-response'] ?? true) {

        }
    }
}
