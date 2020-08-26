<?php

namespace W2w\Laravel\Apie\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Madewithlove\IlluminatePsrCacheBridge\Laravel\CacheItemPool;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use W2w\Laravel\Apie\Plugins\Illuminate\DataLayers\StatusFromDatabaseRetriever;
use W2w\Laravel\Apie\Plugins\Illuminate6Cache\Illuminate6CachePlugin;
use W2w\Laravel\Apie\Plugins\Illuminate\IlluminatePlugin;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\DataLayers\TranslationRetriever;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\IlluminateTranslationPlugin;
use W2w\Laravel\Apie\Plugins\PsrCacheBridge\PsrCacheBridgePlugin;
use W2w\Laravel\Apie\Services\ApieContext;
use W2w\Laravel\Apie\Services\ApieExceptionToResponse;
use W2w\Laravel\Apie\Services\FileStorageDataLayerContainer;
use W2w\Lib\Apie\Apie;
use W2w\Lib\Apie\Core\ApiResourceFacade;
use W2w\Lib\Apie\Core\ApiResourcePersister;
use W2w\Lib\Apie\Core\ApiResourceRetriever;
use W2w\Lib\Apie\Core\ClassResourceConverter;
use W2w\Lib\Apie\Core\Resources\ApiResources;
use W2w\Lib\Apie\Core\Resources\ApiResourcesInterface;
use W2w\Lib\Apie\DefaultApie;
use W2w\Lib\Apie\Exceptions\InvalidClassTypeException;
use W2w\Lib\Apie\Interfaces\ResourceSerializerInterface;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSpecGenerator;
use W2w\Lib\Apie\Plugins\ApplicationInfo\DataLayers\ApplicationInfoRetriever;
use W2w\Lib\Apie\Plugins\Core\Normalizers\ApieObjectNormalizer;
use W2w\Lib\Apie\Plugins\Core\Normalizers\ContextualNormalizer;
use W2w\Lib\Apie\Plugins\Core\Serializers\SymfonySerializerAdapter;
use W2w\Lib\Apie\Plugins\FakeAnnotations\FakeAnnotationsPlugin;
use W2w\Lib\Apie\Plugins\FileStorage\DataLayers\FileStorageDataLayer;
use W2w\Lib\Apie\Plugins\Mock\MockPlugin;
use W2w\Lib\Apie\Plugins\StatusCheck\DataLayers\StatusCheckRetriever;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\ObjectAccessInterface;

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
        if (function_exists('resource_path')) {
            $this->publishes(
                [
                    __DIR__ . '/../../resources/lang' => resource_path('lang/vendor/apie'),
                ]
            );
        }

        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'apie');

        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $this->bindApieContextServices();
        $this->bindApieServices();

        if (strpos($this->app->version(), 'Lumen') === false) {
            $this->app->register(ApieLaravelServiceProvider::class);
        } else {
            $this->app->register(ApieLumenServiceProvider::class);
        }

        $this->addStatusResourceServices();
    }

    /**
     * Bind all Apie related services that are on application level. They must be singletons and are not checking
     * at the current active Apie instance.
     */
    private function bindApieContextServices()
    {
        /**
         * apie.config: main config
         */
        $this->app->singleton('apie.config', function () {
            $config = $this->app->get('config');
            $res = ApieConfigResolver::resolveConfig($config->get('apie') ?? []);
            $config->set('apie', $res);
            return $res;
        });

        /**
         * apie.plugins: array of Apie plugins of the main apie instance.
         */
        $this->app->singleton('apie.plugins', function () {
            return $this->getPlugins();
        });

        /**
         * ApieContext::class: get all Apie instances and see which is the current Apie instance.
         */
        $this->app->singleton(ApieContext::class, function () {
            $plugins = $this->app->get('apie.plugins');
            $debug = (bool) config('app.debug');
            $config = $this->app->get('apie.config');
            $cacheFolder = storage_path('app/apie-cache');
            $apie = DefaultApie::createDefaultApie($debug, $plugins, $cacheFolder, false);
            return new ApieContext($this->app, $apie, $config, $config['contexts']);
        });

        /**
         * FileStorageDataLayerContainer::class: creates FileStorageDataLayer for the right Apie instance.
         */
        $this->app->singleton(FileStorageDataLayerContainer::class, function () {
            return new FileStorageDataLayerContainer(
                storage_path('app/api-file-storage'),
                $this->app->get(ApieContext::class)
            );
        });
    }

    private function getPlugins(): array
    {
        $plugins = [];
        $config = $this->app->get('apie.config');
        if (!empty($config['mock'])) {
            $plugins[] = new MockPlugin($config['mock-skipped-resources']);
        }
        foreach ($this->app->tagged(Apie::class) as $plugin) {
            $plugins[] = $plugin;
        }
        foreach ($config['plugins'] as $plugin) {
            $plugins[] = $this->app->make($plugin);
        }
        if (!empty($config['resource-config'])) {
            $plugins[] = new FakeAnnotationsPlugin($config['resource-config']);
        }
        if (!empty($config['translations'])) {
            $plugins[] = new IlluminateTranslationPlugin(
                $config['translations'],
                $this->app->make('translator'),
                $this->app
            );
        }
        if (!empty($config['caching'])) {
            if ($this->app->bound('cache.psr6')) {
                $plugins[] = new Illuminate6CachePlugin($this->app);
            } elseif (class_exists(CacheItemPool::class)) {
                $plugins[] = new PsrCacheBridgePlugin($this->app);
            }
        }
        $plugins[] = new IlluminatePlugin($this->app, $config);
        return $plugins;
    }

    private function bindApieServices()
    {
        /**
         * ApiResourcesInterface::class: get all resources of the current Apie instance.
         */
        $this->app->bind(ApiResourcesInterface::class, function () {
            return new ApiResources($this->app->get(Apie::class)->getResources());
        });

        /**
         * ApiResourcePersister: call the correct data layer persist functionality.
         */
        $this->app->bind(ApiResourcePersister::class, function () {
            return new ApiResourcePersister($this->app->get(Apie::class)->getApiResourceMetadataFactory());
        });

        /**
         * ApiResourcePersister: call the correct data layer retrieve functionality.
         */
        $this->app->bind(ApiResourceRetriever::class, function () {
            return new ApiResourceRetriever($this->app->get(Apie::class)->getApiResourceMetadataFactory());
        });

        /**
         * Apie::class: current Apie instance.
         */
        $this->app->bind(Apie::class, function () {
            /** @var ApieContext $context */
            $context = $this->app->get(ApieContext::class)->getActiveContext();
            return $context->getApie();
        });

        /**
         * IlluminatePlugin::class: current IlluminatePlugin instance
         */
        $this->app->bind(IlluminatePlugin::class, function () {
            /** @var Apie $apie */
            $apie = $this->app->get(Apie::class);
            return $apie->getPlugin(IlluminatePlugin::class);
        });

        /**
         * ApplicationInfoRetriever::class: get retriever for Application Info of current apie instance.
         */
        $this->app->bind(ApplicationInfoRetriever::class, function () {
            /** @var IlluminatePlugin $laravelPlugin */
            $laravelPlugin = $this->app->get(IlluminatePlugin::class);
            $config = $laravelPlugin->getLaravelConfig();
            return new ApplicationInfoRetriever(
                config('app.name'),
                config('app.env'),
                $config['metadata']['hash'],
                config('app.debug')
            );
        });

        /**
         * FileStorageDataLayer::class: get file storage data layer for current apie instance.
         */
        $this->app->bind(FileStorageDataLayer::class, function () {
            return $this->app->get(FileStorageDataLayerContainer::class)->getCurrentFileStorageDataLayer();
        });

        $this->app->singleton(TranslationRetriever::class);

        /**
         * ApieExceptionToResponse::class: converts exception to an Apie response.
         */
        $this->app->bind(ApieExceptionToResponse::class, function () {
            /** @var IlluminatePlugin $laravelPlugin */
            $laravelPlugin = $this->app->get(IlluminatePlugin::class);
            $config = $laravelPlugin->getLaravelConfig();
            $mapping = $config['exception-mapping'];
            return new ApieExceptionToResponse($this->app->make(HttpFoundationFactory::class), $mapping);
        });

        /**
         * Serializer::class: gets the Symfony serializer.
         */
        $this->app->bind(Serializer::class, function () {
            $serializer = $this->app->get(ResourceSerializerInterface::class);
            if (! ($serializer instanceof SymfonySerializerAdapter)) {
                throw new InvalidClassTypeException('resource serializer', SymfonySerializerAdapter::class);
            }
            return $serializer->getSerializer();
        });
        $this->app->bind(SerializerInterface::class, Serializer::class);
        $this->app->bind(NormalizerInterface::class, Serializer::class);
        $this->app->bind(DenormalizerInterface::class, Serializer::class);

        $todo = [
            [ApiResourceFacade::class, 'getApiResourceFacade'],
            [ClassResourceConverter::class, 'getClassResourceConverter'],
            [OpenApiSpecGenerator::class, 'getOpenApiSpecGenerator'],
            [ResourceSerializerInterface::class, 'getResourceSerializer'],
            [NameConverterInterface::class, 'getPropertyConverter'],
            [ObjectAccessInterface::class, 'getObjectAccess']
        ];
        while ($item = array_pop($todo)) {
            $this->registerApieService($item[0], $item[1]);
        }
    }

    private function registerApieService(string $serviceName, string $methodName)
    {
        $this->app->bind($serviceName, function () use ($methodName) {
            return $this->app->get(Apie::class)->$methodName();
        });
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
