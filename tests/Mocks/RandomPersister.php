<?php


namespace W2w\Laravel\Apie\Tests\Mocks;

use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;
use W2w\Lib\Apie\Interfaces\ApiResourcePersisterInterface;

class RandomPersister implements ApiResourcePersisterInterface
{
    /**
     * @var Dispatcher
     */
    private $service;

    public function __construct(Dispatcher $service)
    {
        $this->service = $service;
    }

    public function persistNew($resource, array $context = [])
    {
        throw new RuntimeException(get_class($this->service));
    }

    public function persistExisting($resource, $int, array $context = [])
    {
        throw new RuntimeException(get_class($this->service));
    }

    public function remove(string $resourceClass, $id, array $context)
    {
        throw new RuntimeException(get_class($this->service));
    }
}
