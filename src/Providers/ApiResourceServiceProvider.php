<?php

namespace W2w\Laravel\Apie\Providers;

use DateTimeInterface;
use erasys\OpenApi\Spec\v3\Contact;
use erasys\OpenApi\Spec\v3\Document;
use erasys\OpenApi\Spec\v3\Info;
use erasys\OpenApi\Spec\v3\License;
use erasys\OpenApi\Spec\v3\Schema;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Madewithlove\IlluminatePsrCacheBridge\Laravel\CacheItemPool;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use W2w\Laravel\Apie\Services\DispatchOpenApiSpecGeneratedEvent;
use W2w\Laravel\Apie\Services\Retrievers\DatabaseQueryRetriever;
use W2w\Laravel\Apie\Services\Retrievers\EloquentModelDataLayer;
use W2w\Lib\Apie\ApiResourceFacade;
use W2w\Lib\Apie\ApiResourceFactory;
use W2w\Lib\Apie\IdentifierExtractor;
use W2w\Lib\Apie\Mocks\MockApiResourceDataLayer;
use W2w\Lib\Apie\Mocks\MockApiResourceFactory;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSpecGenerator;
use W2w\Lib\Apie\OpenApiSchema\SchemaGenerator;
use W2w\Lib\Apie\Resources\ApiResources;
use W2w\Lib\Apie\Resources\ApiResourcesInterface;
use W2w\Lib\Apie\Retrievers\ApplicationInfoRetriever;
use W2w\Lib\Apie\Retrievers\FileStorageDataLayer;
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
        if (function_exists('config_path')) {
            $this->publishes(
                [
                    __DIR__ . '/../../config/apie.php' => config_path('apie.php'),
                ]
            );
        }

        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->singleton('apie.config', function () {
            $config = $this->app->get('config');
            return ApieConfigResolver::resolveConfig($config->get('apie') ?? []);
        });

        $this->app->singleton(ApiResourcesInterface::class, function () {
            $config = $this->app->get('apie.config');
            if (! empty($config['resources-service'])) {
                return $this->app->get($config['resources-service']);
            }
            if ($config['resources'] instanceof ApiResourcesInterface) {
                return $config['resources'];
            }
            return new ApiResources($config['resources']);
        });

        $this->app->singleton(ServiceLibraryFactory::class, function () {
            return $this->createServiceLibraryFactory();
        });

        // MainScheduler: service that does all the background processes of api resources.
        //$this->app->singleton(MainScheduler::class, function () {
        //    return new MainScheduler($this->app->tagged(SchedulerInterface::class));
        //});

        // OpenApiSpecGenerator: generated an OpenAPI 3.0 spec file from a list of resources.
        $this->addOpenApiServices();
        $this->app->singleton(OpenApiSpecGenerator::class, function () {
            $config = $this->app->get('apie.config');
            $factory = $this->app->get(ServiceLibraryFactory::class);
            $baseUrl = $config['base-url'] . $config['api-url'];
            if ($this->app->has(Request::class)) {
                $baseUrl = $this->app->get(Request::class)->getSchemeAndHttpHost() . $baseUrl;
            }
            $this->app->get(SchemaGenerator::class);
            $factory->setInfo($this->app->get(Info::class));
            return $factory->getOpenApiSpecGenerator($baseUrl);
        });

        // SchemaGenerator: generates a OpenAPI Schema from a api resource class.
        $this->app->singleton(SchemaGenerator::class, function () {
            $factory = $this->app->get(ServiceLibraryFactory::class);
            $service = $factory->getSchemaGenerator();
            $service->defineSchemaForResource(UuidInterface::class, new Schema(['type' => 'string', 'format' => 'uuid']));
            $service->defineSchemaForResource(DateTimeInterface::class, new Schema(['type' => 'string', 'format' => 'date-time']));
            return $service;
        });

        $this->app->singleton(Serializer::class, function () {
            return $this->app->get(ServiceLibraryFactory::class)->getSerializer();
        });
        $this->app->bind(SerializerInterface::class, Serializer::class);
        $this->app->bind(NormalizerInterface::class, Serializer::class);
        $this->app->bind(DenormalizerInterface::class, Serializer::class);
        $this->app->singleton(CamelCaseToSnakeCaseNameConverter::class);
        $this->app->bind(NameConverterInterface::class, CamelCaseToSnakeCaseNameConverter::class);

        $this->app->singleton(ApplicationInfoRetriever::class, function () {
            $config = $this->app->get('apie.config');
            return new ApplicationInfoRetriever(
                config('app.name'),
                config('app.env'),
                $config['metadata']['hash'],
                config('app.debug')
            );
        });
        $this->app->singleton(EloquentModelDataLayer::class);
        $this->app->singleton(DatabaseQueryRetriever::class);
        $this->app->singleton(FileStorageDataLayer::class, function () {
            return new FileStorageDataLayer(
                storage_path('app/api-file-storage'),
                $this->app->get(ServiceLibraryFactory::class)->getPropertyAccessor()
            );
        });

        // ApiResourceFacade: class that calls all the right services with a simple interface.
        $this->app->singleton(ApiResourceFacade::class, function () {
            return $this->app->get(ServiceLibraryFactory::class)->getApiResourceFacade();
        });

        $this->addStatusResourceServices();

        if (strpos($this->app->version(), 'Lumen') === false) {
            $this->app->register(ApieLaravelServiceProvider::class);
        } else {
            $this->app->register(ApieLumenServiceProvider::class);
        }
    }

    private function addOpenApiServices()
    {
        // Provides contact information to the OpenAPI spec.
        $this->app->singleton(Contact::class, function () {
            $config = $this->app->get('apie.config');
            return new Contact([
                'name'  => $config['metadata']['contact-name'],
                'url'   => $config['metadata']['contact-url'],
                'email' => $config['metadata']['contact-email'],
            ]);
        });

        // Provides license information to the OpenAPI spec.
        $this->app->singleton(License::class, function () {
            $config = $this->app->get('apie.config');
            return new License(
                $config['metadata']['license'],
                $config['metadata']['license-url']
            );
        });

        // Provides OpenAPI info to the OpenAPI spec.
        $this->app->singleton(Info::class, function () {
            $config = $this->app->get('apie.config');
            return new Info(
                $config['metadata']['title'],
                $config['metadata']['version'],
                $config['metadata']['description'],
                [
                    'contact' => $this->app->get(Contact::class),
                    'license' => $this->app->get(License::class),
                ]
            );
        });
    }

    private function createServiceLibraryFactory(): ServiceLibraryFactory
    {
        $config = $this->app->get('apie.config');
        $result = new ServiceLibraryFactory(
            $this->app->get(ApiResourcesInterface::class),
            (bool) config('app.debug'),
            storage_path('app/apie-cache')
        );
        if ($config['resource-config']) {
            $result->overrideAnnotationConfig($config['resource-config']);
        }
        $result->setContainer($this->app);
        if (!config('app.debug')) {
            $this->handleSerializerCache($result);
        }
        $result->runBeforeInstantiation(function () use (&$result) {
            $normalizers = [
            ];
            $taggedNormalizers = $this->app->tagged(NormalizerInterface::class);
            // app->tagged return type is hazy....
            foreach ($taggedNormalizers as $taggedNormalizer) {
                $normalizers[] = $taggedNormalizer;
            }
            $result->setAdditionalNormalizers($normalizers);
        });

        if ($config['mock']) {
            $cachePool = $result->getSerializerCache();

            $result->setApiResourceFactory(
                new MockApiResourceFactory(
                    new MockApiResourceDataLayer(
                        $cachePool,
                        new IdentifierExtractor($result->getPropertyAccessor()),
                        $result->getPropertyAccessor()
                    ),
                    new ApiResourceFactory($this->app),
                    $config['mock-skipped-resources']
                )
            );
        }
        $result->setOpenApiSpecsHook([DispatchOpenApiSpecGeneratedEvent::class, 'onApiGenerated']);

        return $result;
    }

    private function handleSerializerCache(ServiceLibraryFactory $result)
    {
        if ($this->app->bound('cache.psr6')) {
            $result->setSerializerCache($this->app->get('cache.psr6'));
        } elseif (class_exists(CacheItemPool::class)) {
            $repository = $this->app->make(Repository::class);
            $result->setSerializerCache(new CacheItemPool($repository));
        }
    }

    private function addStatusResourceServices()
    {
        $this->app->singleton(StatusFromDatabaseRetriever::class, function () {
            return new StatusFromDatabaseRetriever(config('app.debug'));
        });
        $this->app->tag([StatusFromDatabaseRetriever::class], ['status-check']);
        $this->app->singleton(StatusCheckRetriever::class, function () {
            return new StatusCheckRetriever($this->app->tagged('status-check'));
        });
    }
}
