<?php

namespace W2w\Laravel\Apie\Plugins\Illuminate\DataLayers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;
use W2w\Laravel\Apie\Plugins\Illuminate\Eloquent\EloquentModelSerializer;
use W2w\Laravel\Apie\Plugins\Illuminate\Eloquent\QueryBuilderResolver;
use W2w\Lib\Apie\Core\SearchFilters\SearchFilterFromMetadataTrait;
use W2w\Lib\Apie\Core\SearchFilters\SearchFilterRequest;
use W2w\Lib\Apie\Exceptions\ResourceNotFoundException;
use W2w\Lib\Apie\Interfaces\ApiResourcePersisterInterface;
use W2w\Lib\Apie\Interfaces\ApiResourceRetrieverInterface;
use W2w\Lib\Apie\Interfaces\SearchFilterProviderInterface;

/**
 * Maps a domain object to an eloquent model. Remember that foreign key constraints can be confusing, so it might be
 * a good idea to make your own retriever and persister class if the model becomes more complex.
 *
 * It uses the fill and toArray method of the Eloquent model. Mass alignment is disabled to map the fields as we
 * assume the domain object does the protection.
 */
class EloquentModelDataLayer implements ApiResourceRetrieverInterface, ApiResourcePersisterInterface, SearchFilterProviderInterface
{
    use SearchFilterFromMetadataTrait;

    private $serializer;

    private $queryBuilderResolver;

    /**
     * @param EloquentModelSerializer $serializer
     * @param QueryBuilderResolver $queryBuilderResolver
     */
    public function __construct(EloquentModelSerializer $serializer, QueryBuilderResolver $queryBuilderResolver)
    {
        $this->serializer = $serializer;
        $this->queryBuilderResolver = $queryBuilderResolver;
    }

    /**
     * Retrieves all resources. Since the filtering whether you are allowed to see a model instance is done afterwards,
     * the pagination could show a less amount of records than indicated. This is only for performance reasons.
     *
     * @param  string              $resourceClass
     * @param  array               $context
     * @param  SearchFilterRequest $searchFilterRequest
     * @return array
     */
    public function retrieveAll(string $resourceClass, array $context, SearchFilterRequest $searchFilterRequest): iterable
    {
        $modelClass = $this->getModelClass($resourceClass, $context);

        $queryBuilder = $this->getQueryBuilder($modelClass, $context['queryBuilder'] ?? null);
        return $this->serializer->toList($queryBuilder, $resourceClass, $searchFilterRequest, $context['mapping'] ?? null);
    }

    /**
     * Retrieves a single instance.
     *
     * @param  string $resourceClass
     * @param  mixed  $id
     * @param  array  $context
     * @return mixed
     */
    public function retrieve(string $resourceClass, $id, array $context)
    {
        $modelClass = $this->getModelClass($resourceClass, $context);
        try {
            $modelInstance = $this->getQueryBuilder($modelClass, $context['queryBuilder'] ?? null)->where($context[1] ?? $context['id'] ?? 'id', $id)->firstOrFail();
        } catch (ModelNotFoundException $notFoundException) {
            throw new ResourceNotFoundException($id);
        }
        $result = $this->serializer->toResource($modelInstance, $resourceClass, $context['mapping'] ?? null);

        return $result;
    }

    /**
     * Creates a new Eloquent model from an api resource.
     *
     * @param  mixed $resource
     * @param  array $context
     * @return mixed
     */
    public function persistNew($resource, array $context = [])
    {
        $resourceClass = get_class($resource);
        $modelInstance = $this->serializer->toModel($resource, $this->getModelClass($resourceClass, $context), $context['mapping'] ?? null);

        return $this->serializer->toResource($modelInstance, $resourceClass);
    }

    /**
     * Stores an api resource to an existing Eloquent model instance.
     *
     * @param  mixed $resource
     * @param  mixed $id
     * @param  array $context
     * @return mixed
     */
    public function persistExisting($resource, $id, array $context = [])
    {
        $resourceClass = get_class($resource);
        $modelClass = $this->getModelClass($resourceClass, $context);
        $modelInstance = $this->serializer->toExistingModel($resource, $id, $modelClass, $context['mapping'] ?? null);

        return $this->serializer->toResource($modelInstance, $resourceClass);
    }

    /**
     * Removes a resource from the database.
     *
     * @param string $resourceClass
     * @param mixed  $id
     * @param array  $context
     */
    public function remove(string $resourceClass, $id, array $context)
    {
        $modelClass = $this->getModelClass($resourceClass, $context);
        $modelInstance = $modelInstance = $this->getQueryBuilder($modelClass, $context['queryBuilder'] ?? null)->where($context[1] ?? $context['id'] ?? 'id', $id)->first();
        if (!$modelInstance) {
            return;
        }
        $modelInstance->delete();
    }

    /**
     * Returns the name of the model class associated to a api resource class.
     *
     * @param  string $resourceClass
     * @return string
     */
    private function determineModel(string $resourceClass): string
    {
        $class = str_replace('\\ApiResources\\', '\\Models\\', $resourceClass);
        if (class_exists($class)) {
            return $class;
        }
        return str_replace('\\ApiResources\\', '\\Entities\\', $resourceClass);
    }

    /**
     * Returns the name of the model class associated to a api resource class and a context.
     *
     * @param  string $resourceClass
     * @param  array  $context
     * @return string
     */
    private function getModelClass(string $resourceClass, array $context): string
    {
        $modelClass = $context[0] ?? $context['model'] ?? $this->determineModel($resourceClass);
        if (!class_exists($modelClass)) {
            throw new RuntimeException('Class "' . $modelClass . '" not found!');
        }
        if (!is_a($modelClass, Model::class, true)) {
            throw new RuntimeException('Class "' . $modelClass . '" exists, but is not a Eloquent model!');
        }

        return $modelClass;
    }

    private function getQueryBuilder(string $modelClass, ?string $contextConfig): Builder {
        if (null === $contextConfig) {
            return $modelClass::query();
        }
        return $this->queryBuilderResolver->resolveFromString($contextConfig);
    }
}
