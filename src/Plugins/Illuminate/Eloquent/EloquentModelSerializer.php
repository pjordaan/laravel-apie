<?php
namespace W2w\Laravel\Apie\Plugins\Illuminate\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;
use W2w\Lib\Apie\Core\SearchFilters\SearchFilterRequest;
use W2w\Lib\Apie\Interfaces\ResourceSerializerInterface;
use W2w\Lib\Apie\Plugins\Core\Serializers\SymfonySerializerAdapter;

/**
 * Contains logic to serialize from/to Eloquent models. This is placed in a different class for reusability.
 */
class EloquentModelSerializer
{
    private $serializer;

    public function __construct(ResourceSerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Converts a Query builder into a list of resources.
     *
     * @param Builder                  $builder
     * @param string                   $resourceClass
     * @param SearchFilterRequest|null $searchFilterRequest
     * @param array|null               $mapping
     * @return Model[]
     */
    public function toList(Builder $builder, string $resourceClass, ?SearchFilterRequest $searchFilterRequest, array $mapping = null): array
    {
        if (empty($builder->getQuery()->orders) && empty($builder->getQuery()->unionOrders)) {
            $builder = $builder->orderBy('id', 'ASC');
        }
        if ($searchFilterRequest) {
            $searches = $searchFilterRequest->getSearches();
            if ($mapping !== null) {
                $searches = ArrayRemapper::remap($mapping, $searches);
            }
            $builder = $builder
                ->where($searches)
                ->skip($searchFilterRequest->getOffset())
                ->take($searchFilterRequest->getNumberOfItems());
        }

        $modelInstances = $builder->get();

        return array_map(
            function ($modelInstance) use (&$resourceClass, &$mapping) {
                return $this->toResource($modelInstance, $resourceClass, $mapping);
            },
            iterator_to_array($modelInstances)
        );
    }

    /**
     * Converts resource into a eloquent model. The instance returns is always a new entity.
     *
     * @param mixed      $resource
     * @param string     $modelClass
     * @param array|null $mapping
     * @return Model
     */
    public function toModel($resource, string $modelClass, array $mapping = null): Model
    {
        $array = $this->serializer->normalize($resource, SymfonySerializerAdapter::INTERNAL_FOR_DATALAYER);
        if (!is_array($array)) {
            throw new UnexpectedValueException('Resource ' . get_class($resource) . ' was normalized to a non array field');
        }
        if ($mapping !== null) {
            $array = ArrayRemapper::remap($mapping, $array);
        }
        $modelClass::unguard();
        try {
            $modelInstance = $modelClass::create($array);
        } finally {
            $modelClass::reguard();
        }
        return $modelInstance;
    }

    /**
     * Maps Eloquent model to a class of $resoureClass
     *
     * @param Model      $eloquentModel
     * @param string     $resourceClass
     * @param array|null $mapping
     * @return mixed
     */
    public function toResource(Model $eloquentModel, string $resourceClass, array $mapping = null)
    {
        $array = $eloquentModel->toArray();
        if ($mapping !== null) {
            $array = ArrayRemapper::reverseRemap($mapping, $array);
        }
        return $this->serializer->hydrateWithReflection($array, $resourceClass);
    }

    /**
     * Converts existing resource into a eloquent model. The instance returns is always a new entity.
     *
     * @param mixed $resource
     * @param mixed $id
     * @param string $modelClass
     * @return Model
     */
    public function toExistingModel($resource, $id, string $modelClass, array $mapping = null): Model
    {
        $resourceClass = get_class($resource);
        $modelInstance = $modelClass::where(['id' => $id])->firstOrFail();
        $array = $this->serializer->normalize($resource, SymfonySerializerAdapter::INTERNAL_FOR_DATALAYER);
        if (!is_array($array)) {
            throw new UnexpectedValueException('Resource ' . $resourceClass . ' was normalized to a non array field');
        }
        if ($mapping !== null) {
            $array = ArrayRemapper::remap($mapping, $array);
        }
        unset($array['id']);
        $modelInstance->unguard();
        try {
            $modelInstance->fill($array);
        } finally {
            $modelInstance->reguard();
        }
        $modelInstance->save();
        return $modelInstance;
    }
}
