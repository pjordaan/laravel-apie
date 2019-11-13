<?php
namespace W2w\Laravel\Apie\Tests\Services\Mock;

use W2w\Laravel\Apie\Services\Retrievers\DatabaseQueryRetriever;
use W2w\Lib\Apie\Annotations\ApiResource;

/**
 * @ApiResource(
 *     retrieveClass=DatabaseQueryRetriever::class,
 *     context={
 *         "query_file": "file-does-not-exist.sql"
 *     }
 * )
 */
class MissingFileClassForDatabaseQueryRetriever
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $size;

    /**
     * @var int
     */
    private $count;

    public function __construct(string $id, int $size, int $count)
    {
        $this->id = $id;
        $this->size = $size;
        $this->count = $count;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
