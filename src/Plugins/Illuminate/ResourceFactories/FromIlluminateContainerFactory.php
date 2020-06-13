<?php


namespace W2w\Laravel\Apie\Plugins\Illuminate\ResourceFactories;

use Illuminate\Container\Container;
use Illuminate\Support\Str;
use W2w\Laravel\Apie\Services\ApieContext;
use W2w\Lib\Apie\Annotations\ApiResource;
use W2w\Lib\Apie\Exceptions\InvalidClassTypeException;
use W2w\Lib\Apie\Interfaces\ApiResourceFactoryInterface;
use W2w\Lib\Apie\Interfaces\ApiResourcePersisterInterface;
use W2w\Lib\Apie\Interfaces\ApiResourceRetrieverInterface;

class FromIlluminateContainerFactory implements ApiResourceFactoryInterface
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    /**
     * Returns true if this factory can create this identifier.
     *
     * @param string $identifier
     * @return bool
     */
    public function hasApiResourceRetrieverInstance(string $identifier): bool
    {
        return $this->validIdentifier($identifier);
    }

    /**
     * Gets an instance of ApiResourceRetrieverInstance
     * @param string $identifier
     * @return ApiResourceRetrieverInterface
     */
    public function getApiResourceRetrieverInstance(string $identifier): ApiResourceRetrieverInterface
    {
        $result = $this->container->make($identifier);
        if (!($result instanceof ApiResourceRetrieverInterface)) {
            throw new InvalidClassTypeException($identifier, 'ApiResourceRetrieverInterface');
        }
        return $result;
    }

    /**
     * Returns true if this factory can create this identifier.
     *
     * @param string $identifier
     * @return bool
     */
    public function hasApiResourcePersisterInstance(string $identifier): bool
    {
        return $this->validIdentifier($identifier);
    }

    /**
     * Gets an instance of ApiResourceRetrieverInstance
     * @param string $identifier
     * @return ApiResourcePersisterInterface
     */
    public function getApiResourcePersisterInstance(string $identifier): ApiResourcePersisterInterface
    {
        $result = $this->container->make($identifier);
        if (!($result instanceof ApiResourcePersisterInterface)) {
            throw new InvalidClassTypeException($identifier, 'ApiResourcePersisterInterface');
        }
        return $result;
    }

    private function validIdentifier(string $identifier): bool
    {
        if ($this->container->has($identifier) || $this->container->bound($identifier)) {
            return true;
        }
        if ($this->container->bound(ApieContext::class)) {
            $context = $this->container->make(ApieContext::class);
            foreach ($context->allContexts() as $apieContext) {
                /** @var ApiResource[] $config */
                $config = $apieContext->getConfig('resource-config');
                foreach ($config as $resourceConfigEntry) {
                    if ($identifier === $resourceConfigEntry->retrieveClass) {
                        $this->container->singleton($identifier);
                        return true;
                    }
                    if ($identifier === $resourceConfigEntry->persistClass) {
                        $this->container->singleton($identifier);
                        return true;
                    }
                }
            }
        }
        return Str::startsWith($identifier, 'W2w\Laravel\Apie\Plugins\Illuminate\DataLayers\\');
    }
}
