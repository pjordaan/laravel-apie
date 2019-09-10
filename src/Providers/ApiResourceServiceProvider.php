<?php

namespace W2w\Laravel\Apie\Providers;

use Illuminate\Foundation\Application;
use W2w\Lib\Apie\ApiResourceFacade;
use W2w\Lib\Apie\ApiResourceFactory;
use W2w\Lib\Apie\ApiResourceFactoryInterface;
use W2w\Lib\Apie\ApiResourceMetadataFactory;
use W2w\Lib\Apie\ApiResourcePersister;
use W2w\Lib\Apie\ApiResourceRetriever;
use W2w\Lib\Apie\ApiResources;
use W2w\Lib\Apie\ClassResourceConverter;
use W2w\Lib\Apie\Mock\MockApiResourceFactory;
use W2w\Lib\Apie\Mock\MockApiResourceRetriever;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSpecGenerator;
use W2w\Lib\Apie\Retriever\AppRetriever;
use W2w\Lib\Apie\Retriever\StatusCheckRetriever;
use W2w\Laravel\Apie\Services\StatusCheck\StatusFromDatabaseRetriever;
use DateTimeInterface;
use erasys\OpenApi\Spec\v3\Contact;
use erasys\OpenApi\Spec\v3\Info;
use erasys\OpenApi\Spec\v3\License;
use erasys\OpenApi\Spec\v3\Schema;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;
use Madewithlove\IlluminatePsrCacheBridge\Laravel\CacheItemPool;
use Psr\Cache\CacheItemPoolInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class ApiResourceServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/api-resource.php' => config_path('api-resource.php'),
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $config = $this->app->get(ConfigRepository::class);
        if ($config->get('api-resource.enable-serializer', true)) {
            $this->app->register(SymfonySerializerProvider::class);
        }
        // ApiResources: returns all class names that should be used as class resource.
        $this->app->singleton(ApiResources::class, function () use (&$config) {
            return new ApiResources($config->get('api-resource.resources', []));
        });

        // MainScheduler: service that does all the background processes of api resources.
        //$this->app->singleton(MainScheduler::class, function () {
        //    return new MainScheduler($this->app->tagged(SchedulerInterface::class));
        //});

        // OpenApiSpecGenerator: generated an OpenAPI 3.0 spec file from a list of resources.
        $this->addOpenApiServices();
        $this->app->singleton(OpenApiSpecGenerator::class);
        $this->app->when(OpenApiSpecGenerator::class)
                  ->needs('$baseUrl')
                  ->give($config->get('api-resource.api-url'));

        // SchemaGenerator: generates a OpenAPI Schema from a api resource class.
        $this->app->singleton(SchemaGenerator::class);

        $this->app->extend(SchemaGenerator::class, function ($service) {
            $service->defineSchemaForResource(Uuid::class, new Schema(['type' => 'string', 'format' => 'uuid']));
            $service->defineSchemaForResource(DateTimeInterface::class, new Schema(['type' => 'string', 'format' => 'date-time']));
            return $service;
        });

        // ApiResourceMetadataFactory: service that returns metadata of an api resource.
        $this->app->singleton(ApiResourceMetadataFactory::class);

        // ApiResourceRetriever: service that retrieves api resources.
        $this->app->singleton(ApiResourceRetriever::class);

        // ApiResourcePersister: service that persists api resources.
        $this->app->singleton(ApiResourcePersister::class);

        // ApiResourceFactoryInterface: factory class that creates api resource retriever/factory instances.
        $this->app->singleton(ApiResourceFactory::class);
        $this->app->singleton(MockApiResourceRetriever::class);
        $this->app->singleton(MockApiResourceFactory::class);

        if ($config->get('api-resource.mock', false)) {
            $this->app->singleton('api-resource-mock-cache', function (Application $app) {
                $repository = $app->make(CacheRepository::class);

                return new CacheItemPool($repository);
            });
            $this->app->when(MockApiResourceRetriever::class)
                ->needs(CacheItemPoolInterface::class)
                ->give(function () {
                    return $this->app->get('api-resource-mock-cache');
                });
            $this->app->alias(MockApiResourceFactory::class, ApiResourceFactoryInterface::class);
            $this->app->when(MockApiResourceFactory::class)
                ->needs('$skippedResources')
                ->give(config('api-resource.mock-skipped-resources'));
        } else {
            $this->app->alias(ApiResourceFactory::class, ApiResourceFactoryInterface::class);
        }

        // ClassResourceConverter: converts from url slug to class name and vice versa.
        $this->app->singleton(ClassResourceConverter::class, function (Application $app) use (&$config) {
            return new ClassResourceConverter(
                $app->get(NameConverterInterface::class),
                $app->get(ApiResources::class),
                $config->get('app.debug')
            );
        });

        $this->app->singleton(AppRetriever::class);
        $this->app->singleton(EloquentModelRetriever::class);
        $this->app->singleton(DatabaseQueryRetriever::class);

        // ApiResourceFacade: class that calls all the right services with a simple interface.
        $this->app->singleton(ApiResourceFacade::class);

        $this->addStatusResourceServices();

        $this->loadRoutesFrom(__DIR__ . '/../../config/routes.php');
    }

    private function addOpenApiServices()
    {
        // Provides contact information to the OpenAPI spec.
        $this->app->singleton(Contact::class, function () {
            return new Contact([
                'name'  => config('api-resource.metadata.contact-name'),
                'url'   => config('api-resource.metadata.contact-url'),
                'email' => config('api-resource.metadata.contact-email'),
            ]);
        });

        // Provides license information to the OpenAPI spec.
        $this->app->singleton(License::class, function () {
            return new License(
                config('api-resource.metadata.license'),
                config('api-resource.metadata.license-url')
            );
        });

        // Provides OpenAPI info to the OpenAPI spec.
        $this->app->singleton(Info::class, function (Application $app) {
            return new Info(
                config('api-resource.metadata.title'),
                config('api-resource.metadata.version'),
                config('api-resource.metadata.description'),
                [
                    'contact' => $this->app->get(Contact::class),
                    'license' => $this->app->get(License::class),
                ]
            );
        });
    }

    private function addStatusResourceServices()
    {
        $this->app->singleton(StatusFromDatabaseRetriever::class, function () {
            return new StatusFromDatabaseRetriever(config('app.debug'));
        });
        $this->app->tag([StatusFromDatabaseRetriever::class], 'status-check');
        $this->app->singleton(StatusCheckRetriever::class, function () {
            return new StatusCheckRetriever($this->app->tagged('status-check'));
        });
    }
}
