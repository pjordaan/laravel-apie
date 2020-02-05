<?php


namespace W2w\Laravel\Apie\Plugins\Illuminate\ResourceFactories;

use Illuminate\Container\Container;
use Illuminate\Support\Str;
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
        return $this->container->has($identifier)
            || $this->container->bound($identifier)
            || Str::startsWith($identifier, 'W2w\Laravel\Apie\Plugins\Illuminate\DataLayers\\');
    }
}
