<?php
namespace W2w\Laravel\Apie\Plugins\Illuminate;

use erasys\OpenApi\Spec\v3\Contact;
use erasys\OpenApi\Spec\v3\Document;
use erasys\OpenApi\Spec\v3\Info;
use erasys\OpenApi\Spec\v3\License;
use erasys\OpenApi\Spec\v3\Schema;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use W2w\Laravel\Apie\Events\OpenApiSpecGenerated;
use W2w\Laravel\Apie\Plugins\Illuminate\Encoders\DefaultContentTypeFormatRetriever;
use W2w\Laravel\Apie\Plugins\Illuminate\Normalizers\CollectionNormalizer;
use W2w\Laravel\Apie\Plugins\Illuminate\Normalizers\LazyCollectionNormalizer;
use W2w\Laravel\Apie\Plugins\Illuminate\ResourceFactories\FromIlluminateContainerFactory;
use W2w\Laravel\Apie\Plugins\Illuminate\Schema\CollectionSchemaBuilder;
use W2w\Laravel\Apie\Providers\ApieConfigResolver;
use W2w\Lib\Apie\Core\Resources\ApiResourcesInterface;
use W2w\Lib\Apie\Exceptions\InvalidClassTypeException;
use W2w\Lib\Apie\Interfaces\ApiResourceFactoryInterface;
use W2w\Lib\Apie\Interfaces\FormatRetrieverInterface;
use W2w\Lib\Apie\PluginInterfaces\ApieConfigInterface;
use W2w\Lib\Apie\PluginInterfaces\ApiResourceFactoryProviderInterface;
use W2w\Lib\Apie\PluginInterfaces\EncoderProviderInterface;
use W2w\Lib\Apie\PluginInterfaces\NormalizerProviderInterface;
use W2w\Lib\Apie\PluginInterfaces\ObjectAccessProviderInterface;
use W2w\Lib\Apie\PluginInterfaces\OpenApiEventProviderInterface;
use W2w\Lib\Apie\PluginInterfaces\OpenApiInfoProviderInterface;
use W2w\Lib\Apie\PluginInterfaces\ResourceProviderInterface;
use W2w\Lib\Apie\PluginInterfaces\SchemaProviderInterface;
use W2w\Lib\Apie\PluginInterfaces\SubActionsProviderInterface;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\ObjectAccessInterface;

class IlluminatePlugin implements ObjectAccessProviderInterface, ResourceProviderInterface, ApieConfigInterface, OpenApiInfoProviderInterface, ApiResourceFactoryProviderInterface, EncoderProviderInterface, NormalizerProviderInterface, OpenApiEventProviderInterface, SchemaProviderInterface, SubActionsProviderInterface
{
    private $container;

    private $resolvedConfig;

    public function __construct(Container $container, array $config)
    {
        $this->container = $container;
        $this->resolvedConfig = ApieConfigResolver::resolveConfig($config);
    }

    public function getLaravelConfig(): array
    {
        return $this->resolvedConfig;
    }

    /**
     * Returns a list of Api resources.
     *
     * @return string[]
     */
    public function getResources(): array
    {
        if (!empty($this->resolvedConfig['resources-service'])) {
            $resources = $this->container->make($this->resolvedConfig['resources-service']);
            if (!($resources instanceof ApiResourcesInterface)) {
                throw new InvalidClassTypeException('resources-service', ApiResourcesInterface::class);
            }
            return $resources->getApiResources();
        }
        return $this->resolvedConfig['resources'];
    }

    /**
     * {@inheritDoc}
     */
    public function getBaseUrl(): string
    {
        $baseUrl = $this->resolvedConfig['base-url'] . $this->resolvedConfig['api-url'];
        if ($this->container->has(Request::class)) {
            $baseUrl = $this->container->get(Request::class)->getSchemeAndHttpHost() . $baseUrl;
        }
        return $baseUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function createInfo(): Info
    {
        return new Info(
            $this->resolvedConfig['metadata']['title'],
            $this->resolvedConfig['metadata']['version'],
            $this->resolvedConfig['metadata']['description'],
            [
                'contact' => new Contact([
                    'name'  => $this->resolvedConfig['metadata']['contact-name'],
                    'url'   => $this->resolvedConfig['metadata']['contact-url'],
                    'email' => $this->resolvedConfig['metadata']['contact-email'],
                ]),
                'license' => new License(
                    $this->resolvedConfig['metadata']['license'],
                    $this->resolvedConfig['metadata']['license-url']
                ),
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getApiResourceFactory(): ApiResourceFactoryInterface
    {
        return new FromIlluminateContainerFactory($this->container);
    }

    /**
     * {@inheritDoc}
     */
    public function getEncoders(): array
    {
        $res = [];
        // container->tagged has hazy return value...
        foreach ($this->container->tagged(EncoderInterface::class) as $normalizer) {
            $res[] = $normalizer;
        };
        return $res;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormatRetriever(): FormatRetrieverInterface
    {
        return new DefaultContentTypeFormatRetriever();
    }

    /**
     * {@inheritDoc}
     */
    public function getNormalizers(): array
    {
        $res = [];
        // container->tagged has hazy return value...
        foreach ($this->container->tagged(NormalizerInterface::class) as $normalizer) {
            $res[] = $normalizer;
        };
        $res[] = new CollectionNormalizer();
        if (class_exists(LazyCollection::class)) {
            $res[] = new LazyCollectionNormalizer();
        }
        return $res;
    }

    /**
     * {@inheritDoc}
     */
    public function onOpenApiDocGenerated(Document $document): Document
    {
        $event = new OpenApiSpecGenerated($document);
        $this->container->get('events')->dispatch($event);
        return $event->getDocument();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinedStaticData(): array
    {
        return [
            Model::class => new Schema([
                'type' => 'object'
            ]),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDynamicSchemaLogic(): array
    {
        $schemas = [
            Collection::class => new CollectionSchemaBuilder(),
        ];
        if (class_exists(LazyCollection::class)) {
            $schemas[LazyCollection::class] = new LazyCollectionNormalizer();
        }
        return $schemas;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubActions()
    {
        $subActions = $this->resolvedConfig['subactions'];
        $results = [];
        foreach ($subActions as $slug => $actions) {
            $results[$slug] = [];
            foreach ($actions as $action) {
                $results[$slug][] = $this->container->make($action);
            }
        }
        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function getObjectAccesses(): array
    {
        $objectAccess = $this->resolvedConfig['object-access'];
        $results = [];
        foreach ($objectAccess as $key => $objectAccessClass) {
            $service = $this->container->make($objectAccessClass);
            if (!($service instanceof ObjectAccessInterface)) {
                throw new InvalidClassTypeException($objectAccessClass, 'ObjectAccessInterface');
            }
            $results[$key] = $service;
        }
        return $results;
    }
}
