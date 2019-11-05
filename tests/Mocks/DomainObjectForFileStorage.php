<?php
namespace W2w\Laravel\Apie\Tests\Mocks;

use Ramsey\Uuid\Uuid;
use W2w\Lib\Apie\Annotations\ApiResource;
use W2w\Lib\Apie\Retrievers\FileStorageRetriever;

/**
 * @ApiResource(
 *     persistClass=FileStorageRetriever::class,
 *     retrieveClass=FileStorageRetriever::class
 * )
 */
class DomainObjectForFileStorage
{
    private $id;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
