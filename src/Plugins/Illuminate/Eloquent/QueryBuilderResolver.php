<?php

namespace W2w\Laravel\Apie\Plugins\Illuminate\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Psr\Container\ContainerInterface;
use W2w\Lib\Apie\Exceptions\BadConfigurationException;

class QueryBuilderResolver
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function resolveFromString(string $input): Builder
    {
        if (!preg_match('/^(?<service>[a-z0-9A-Z_\\\\]+)@(?<method>[a-z0-9A-Z_-]+)$/', $input, $matches)) {
            throw new BadConfigurationException('"' . $input . '" is not in the format <service>@<method>');
        }
        $service = $this->container->get($matches['service']);
        $method = $matches['method'];
        if (!is_callable([$service, $method])) {
            throw new BadConfigurationException('"' . $method . '" is not callable in service "' . $matches['service'] . '"');
        }

        // TODO: typechecking?
        return $service->$method();
    }
}
