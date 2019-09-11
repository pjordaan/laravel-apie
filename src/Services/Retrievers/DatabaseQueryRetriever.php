<?php

namespace W2w\Laravel\Apie\Services\Retrievers;

use Illuminate\Database\DatabaseManager;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Does a SQL query and maps the output to a domain object. The result set should have an id returned to retrieve
 * single records.
 */
class DatabaseQueryRetriever implements ApiResourceRetrieverInterface
{
    private $db;

    private $normalizer;

    private $denormalizer;

    /**
     * @param DatabaseManager $db
     * @param NormalizerInterface $normalizer
     * @param DenormalizerInterface $denormalizer
     */
    public function __construct(DatabaseManager $db, NormalizerInterface $normalizer, DenormalizerInterface $denormalizer)
    {
        $this->db = $db;
        $this->normalizer = $normalizer;
        $this->denormalizer = $denormalizer;
    }

    /**
     * Retrieves a single resource.
     *
     * @param string $resourceClass
     * @param $id
     * @param array $context
     * @return array|object
     */
    public function retrieve(string $resourceClass, $id, array $context)
    {
        $query = $this->getFindQuery($resourceClass, $context);
        if (empty($query)) {
            throw new RuntimeException('Resource ' . $resourceClass . ' misses a query_single or query_single_file option in the ApiResource annotation');
        }
        $result = $this->db->select($this->db->raw($query), ['id' => $id]);
        if (empty($result)) {
            throw new HttpException(404, "$id not found");
        }

        return $this->denormalizer->denormalize($result[0], $resourceClass);
    }

    /**
     * Retrieves all results.
     *
     * @param string $resourceClass
     * @param array $context
     * @param int $pageIndex
     * @param int $numberOfItems
     * @return iterable
     */
    public function retrieveAll(string $resourceClass, array $context, int $pageIndex, int $numberOfItems): iterable
    {
        $query = $this->getAllQuery($resourceClass, $context);

        if (empty($query)) {
            throw new RuntimeException('Resource ' . $resourceClass . ' misses a query or query_file option in the ApiResource annotation');
        }

        $result = $this->db->select($this->db->raw($query . ' LIMIT :offset, :limit'), ['offset' => $pageIndex, 'limit' => $numberOfItems]);

        return $this->denormalizer->denormalize($result, $resourceClass . '[]');
    }

    /**
     * Returns the query to retrieve all rows.
     *
     * @param string $resourceClass
     * @param array $context
     * @return string
     */
    private function getAllQuery(string $resourceClass, array $context): string
    {
        if (!empty($context['query_file'])) {
            $filename = dirname((new ReflectionClass($resourceClass))->getFileName()) . DIRECTORY_SEPARATOR . $context['query_file'];
            if (!file_exists($filename)) {
                throw new RuntimeException('File ' . $filename . ' not found!');
            }
            $context['query'] = file_get_contents($filename);
        }

        return $context['query'] ?? null;
    }

    /**
     * Returns the query to retrieve a single resource.
     *
     * @param string $resourceClass
     * @param array $context
     * @return string
     */
    private function getFindQuery(string $resourceClass, array $context): string
    {
        if (!empty($context['query_single_file'])) {
            $filename = dirname((new ReflectionClass($resourceClass))->getFileName()) . DIRECTORY_SEPARATOR . $context['query_single_file'];
            if (!file_exists($filename)) {
                throw new RuntimeException('File ' . $filename . ' not found!');
            }
            $context['query_single'] = file_get_contents($filename);
        }
        if (empty($context['query_single'])) {
            $allQuery = $this->getAllQuery($resourceClass, $context);
            if (!empty($allQuery)) {
                return 'SELECT * FROM (' . $allQuery . ')  AS subquery WHERE id = :id';
            }
        }

        return $context['query_single'] ?? null;
    }
}
