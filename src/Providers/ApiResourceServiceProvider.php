<?php

namespace W2w\Laravel\Apie\Providers;

use DateTimeInterface;
use erasys\OpenApi\Spec\v3\Contact;
use erasys\OpenApi\Spec\v3\Info;
use erasys\OpenApi\Spec\v3\License;
use erasys\OpenApi\Spec\v3\Schema;
use GBProd\UuidNormalizer\UuidDenormalizer;
use GBProd\UuidNormalizer\UuidNormalizer;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Madewithlove\IlluminatePsrCacheBridge\Laravel\CacheItemPool;
use Ramsey\Uuid\Uuid;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use W2w\Laravel\Apie\Controllers\SwaggerUiController;
use W2w\Laravel\Apie\Services\Retrievers\DatabaseQueryRetriever;
use W2w\Laravel\Apie\Services\Retrievers\EloquentModelRetriever;
use W2w\Lib\Apie\ApiResourceFacade;
use W2w\Lib\Apie\ApiResourceFactory;
use W2w\Lib\Apie\Mocks\MockApiResourceFactory;
use W2w\Lib\Apie\Mocks\MockApiResourceRetriever;
use W2w\Lib\Apie\Normalizers\ValueObjectNormalizer;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSpecGenerator;
use W2w\Lib\Apie\OpenApiSchema\SchemaGenerator;
use W2w\Lib\Apie\Resources\ApiResources;
use W2w\Lib\Apie\Resources\ApiResourcesInterface;
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
        if (function_exists('config_path')) {
            $this->publishes(
                [
                    __DIR__ . '/../../config/apie.php' => config_path('apie.php'),
                ]
            );
        }

        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        $config = $this->app->get('apie.config');
        if ($config['disable-routes']) {
            return;
        }
        if (strpos($this->app->version(), 'Lumen') === false) {
            if ($config['swagger-ui-test-page']) {
                $this->loadRoutesFrom(__DIR__ . '/../../config/routes-openapi.php');
            }
            $this->loadRoutesFrom(__DIR__ . '/../../config/routes.php');
            return;
        }
        if ($config['swagger-ui-test-page']) {
            require __DIR__ . '/../../config/routes-lumen-openapi.php';
        }
        require __DIR__ . '/../../config/routes-lumen.php';
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->singleton('apie.config', function () {
            $config = $this->app->get('config');

            $resolver = new OptionsResolver();
            $defaults = require __DIR__ . '/../../config/apie.php';
            $resolver->setDefaults($defaults);
            return $resolver->resolve($config->get('apie') ?? []);
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
            $config = $this->app->get('apie.config');
            $result = new ServiceLibraryFactory(
                $this->app->get(ApiResourcesInterface::class),
                (bool) config('app.debug'),
                storage_path('apie-cache')
            );
            $result->setContainer($this->app);
            $result->runBeforeInstantiation(function () use (&$result) {
                $normalizers = [
                    new UuidNormalizer(),
                    new UuidDenormalizer(),
                    new ValueObjectNormalizer(),
                ];
                $taggedNormalizers = $this->app->tagged(NormalizerInterface::class);
                // app->tagged return type is hazy....
                foreach ($taggedNormalizers as $taggedNormalizer) {
                    $normalizers[] = $taggedNormalizer;
                }
                if (!config('app.debug')) {
                    $repository = $this->app->make(Repository::class);
                    $result->setSerializerCache(new CacheItemPool($repository));
                }
                $result->setAdditionalNormalizers($normalizers);
            });

            if ($config['mock']) {
                $cachePool = new CacheItemPool($this->app->make(CacheRepository::class));

                $result->setApiResourceFactory(
                    new MockApiResourceFactory(
                        new MockApiResourceRetriever(
                            $cachePool,
                            $result->getPropertyAccessor()
                        ),
                        new ApiResourceFactory($this->app),
                        $config['mock-skipped-resources']
                    )
                );
            }

            return $result;
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
            $service->defineSchemaForResource(Uuid::class, new Schema(['type' => 'string', 'format' => 'uuid']));
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

        $this->app->singleton(AppRetriever::class, function () {
            $config = $this->app->get('apie.config');
            return new AppRetriever(
                config('app.name'),
                config('app.env'),
                $config['metadata']['hash'],
                config('app.debug')
            );
        });
        $this->app->singleton(EloquentModelRetriever::class);
        $this->app->singleton(DatabaseQueryRetriever::class);
        $this->app->singleton(FileStorageRetriever::class, function () {
            return new FileStorageRetriever(
                storage_path('api-file-storage'),
                $this->app->get(ServiceLibraryFactory::class)->getPropertyAccessor()
            );
        });

        // ApiResourceFacade: class that calls all the right services with a simple interface.
        $this->app->singleton(ApiResourceFacade::class, function () {
            return $this->app->get(ServiceLibraryFactory::class)->getApiResourceFacade();
        });

        $this->app->bind(SwaggerUiController::class, function () {
            if ($this->app->has(UrlGenerator::class)) {
                $urlGenerator = $this->app->get(UrlGenerator::class);
            } else {
                $urlGenerator = new \Laravel\Lumen\Routing\UrlGenerator($this->app);
            }
            return new SwaggerUiController($urlGenerator, __DIR__ . '/../../resources/open-api.html');
        });

        $this->addStatusResourceServices();
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
