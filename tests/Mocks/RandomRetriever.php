<?php


namespace W2w\Laravel\Apie\Tests\Mocks;

use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;
use W2w\Lib\Apie\Core\SearchFilters\SearchFilterRequest;
use W2w\Lib\Apie\Interfaces\ApiResourceRetrieverInterface;

class RandomRetriever implements ApiResourceRetrieverInterface
{
    /**
     * @var Dispatcher
     */
    private $service;

    public function __construct(Dispatcher $service)
    {
        $this->service = $service;
    }

    public function retrieve(string $resourceClass, $id, array $context)
    {
        throw new RuntimeException(get_class($this->service));
    }

    public function retrieveAll(string $resourceClass, array $context, SearchFilterRequest $searchFilterRequest
    ): iterable {
        return [];
    }
}
