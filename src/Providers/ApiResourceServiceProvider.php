<?php

namespace W2w\Laravel\Apie\Providers;

use DateTimeInterface;
use erasys\OpenApi\Spec\v3\Contact;
use erasys\OpenApi\Spec\v3\Info;
use erasys\OpenApi\Spec\v3\License;
use erasys\OpenApi\Spec\v3\Schema;
use GBProd\UuidNormalizer\UuidDenormalizer;
use GBProd\UuidNormalizer\UuidNormalizer;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;
use Madewithlove\IlluminatePsrCacheBridge\Laravel\CacheItemPool;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use W2w\Laravel\Apie\Services\Retrievers\DatabaseQueryRetriever;
use W2w\Laravel\Apie\Services\Retrievers\EloquentModelRetriever;
use W2w\Lib\Apie\ApiResourceFacade;
use W2w\Lib\Apie\ApiResourceFactory;
use W2w\Lib\Apie\Mocks\MockApiResourceFactory;
use W2w\Lib\Apie\Mocks\MockApiResourceRetriever;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSpecGenerator;
use W2w\Lib\Apie\OpenApiSchema\SchemaGenerator;
use W2w\Lib\Apie\Retrievers\AppRetriever;
use W2w\Lib\Apie\Retrievers\FileStorageRetriever;
use W2w\Lib\Apie\Retrievers\StatusCheckRetriever;
use W2w\Laravel\Apie\Services\StatusChecks\StatusFromDatabaseRetriever;
use W2w\Lib\Apie\ServiceLibraryFactory;

/**
 * Install apie classes to Laravel.
 */
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
        $factory = new ServiceLibraryFactory(
            $config->get('api-resource.resources'),
            (bool) $config->get('app.debug'),
            storage_path('api-resource-cache')
        );
        $factory->setContainer($this->app);
        $factory->runBeforeInstantiation(function () use (&$factory) {
            $normalizers = $this->app->tagged(NormalizerInterface::class);
            $normalizers[] = new UuidNormalizer();
            $normalizers[] = new UuidDenormalizer();
            $factory->setAdditionalNormalizers($normalizers);
        });

        if (!$config->get('app.debug')) {
            $repository = $this->app->make(Repository::class);
            $factory->setSerializerCache(new CacheItemPool($repository));
        }

        // MainScheduler: service that does all the background processes of api resources.
        //$this->app->singleton(MainScheduler::class, function () {
        //    return new MainScheduler($this->app->tagged(SchedulerInterface::class));
        //});

        // OpenApiSpecGenerator: generated an OpenAPI 3.0 spec file from a list of resources.
        $this->addOpenApiServices();
        $this->app->singleton(OpenApiSpecGenerator::class, function () use (&$factory, &$config) {
            $factory->setInfo($this->app->get(Info::class));
            return $factory->getOpenApiSpecGenerator($config->get('api-resource.base-url') . $config->get('api-resource.api-url'));
        });

        // SchemaGenerator: generates a OpenAPI Schema from a api resource class.
        $this->app->singleton(SchemaGenerator::class, function () use (&$factory) {
            $service = $factory->getSchemaGenerator();
            $service->defineSchemaForResource(Uuid::class, new Schema(['type' => 'string', 'format' => 'uuid']));
            $service->defineSchemaForResource(DateTimeInterface::class, new Schema(['type' => 'string', 'format' => 'date-time']));
            return $service;
        });

        if ($config->get('api-resource.mock', false)) {
            $this->app->singleton('api-resource-mock-cache', function (Container $app) use (&$factory) {
                $repository = $app->make(CacheRepository::class);

                return new CacheItemPool($repository);
            });
            $factory->setApiResourceFactory(
                new MockApiResourceFactory(
                    new MockApiResourceRetriever(
                        $this->app->get('api-resource-mock-cache'),
                        $factory->getPropertyAccessor()
                    ),
                    new ApiResourceFactory($this->app),
                    config('api-resource.mock-skipped-resources')
                )
            );
        }

        $this->app->singleton(AppRetriever::class, function (Container $app) use (&$config) {
            return new AppRetriever(
                $config->get('app.name'),
                $config->get('app.env'),
                $config->get('api-resource.metadata.hash'),
                $config->get('app.debug')
            );
        });
        $this->app->singleton(EloquentModelRetriever::class);
        $this->app->singleton(DatabaseQueryRetriever::class);
        $this->app->singleton(FileStorageRetriever::class, function () use ($factory) {
            return new FileStorageRetriever(storage_path('api-file-storage'), $factory->getPropertyAccessor());
        });

        // ApiResourceFacade: class that calls all the right services with a simple interface.
        $this->app->singleton(ApiResourceFacade::class, function () use ($factory) {
            return $factory->getApiResourceFacade();
        });

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
        $this->app->singleton(Info::class, function () {
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
