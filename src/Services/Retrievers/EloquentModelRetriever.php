<?php

namespace W2w\Laravel\Apie\Services\Retrievers;

use UnexpectedValueException;
use W2w\Lib\Apie\Normalizers\ContextualNormalizer;
use W2w\Lib\Apie\Normalizers\EvilReflectionPropertyNormalizer;
use W2w\Lib\Apie\Persisters\ApiResourcePersisterInterface;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Maps a domain object to an eloquent model. Remember that foreign key constraints can be confusing, so it might be
 * a good idea to make your own retriever and persister class if the model becomes more complex.
 *
 * It uses the fill and toArray method of the Eloquent model. Mass alignment is disabled to map the fields as we
 * assume the domain object does the protection.
 */
class EloquentModelRetriever implements ApiResourceRetrieverInterface, ApiResourcePersisterInterface
{
    private $normalizer;

    private $denormalizer;

    private $gate;

    /**
     * @param NormalizerInterface $normalizer
     * @param DenormalizerInterface $denormalizer
     * @param Gate $gate
     */
    public function __construct(NormalizerInterface $normalizer, DenormalizerInterface $denormalizer, Gate $gate)
    {
        $this->normalizer = $normalizer;
        $this->denormalizer = $denormalizer;
        $this->gate = $gate;
    }

    /**
     * Retrieves a single instance.
     *
     * @param string $resourceClass
     * @param $id
     * @param array $context
     * @return Model
     */
    public function retrieve(string $resourceClass, $id, array $context)
    {
        $modelClass = $this->getModelClass($resourceClass, $context);
        $modelInstance = $modelClass::where($context[1] ?? $context['id'] ?? 'id', $id)->firstOrFail();
        $result = $this->denormalize($modelInstance->toArray(), $resourceClass);
        $this->gate->authorize('get', $result);

        return $result;
    }

    /**
     * Retrieves all resources. Since the filtering whether you are allowed to see a model instance is done afterwards,
     * the pagination could show a less amount of records than indicated. This is only for performance reasons.
     *
     * @param string $resourceClass
     * @param array $context
     * @param int $pageIndex
     * @param int $numberOfItems
     * @return Model[]
     */
    public function retrieveAll(string $resourceClass, array $context, int $pageIndex, int $numberOfItems): iterable
    {
        $modelClass = $this->getModelClass($resourceClass, $context);
        $modelInstances = $modelClass::where([])->orderBy('id', 'ASC')
            ->skip($pageIndex * $numberOfItems)
            ->take($numberOfItems)
            ->get();

        return array_filter(
            array_map(function ($modelInstance) use (&$resourceClass) {
                return $this->denormalize($modelInstance->toArray(), $resourceClass);
            }, iterator_to_array($modelInstances)),
            function ($resource) {
                return $this->gate->allows('get', $resource);
            }
        );
    }

    /**
     * Creates a new Eloquent model from an api resource.
     *
     * @param mixed $resource
     * @param array $context
     * @return Model
     */
    public function persistNew($resource, array $context = [])
    {
        $this->gate->authorize('post', $resource);
        $resourceClass = get_class($resource);
        $array = $this->normalizer->normalize($resource);
        if (!is_array($array)) {
            throw new UnexpectedValueException('Resource ' . get_class($resource) . ' was normalized to a non array field');
        }
        $modelClass = $this->getModelClass($resourceClass, $context);
        $modelClass::unguard();
        try {
            $modelInstance = $modelClass::create($array);
        } finally {
            $modelClass::reguard();
        }

        return $this->denormalize($modelInstance->toArray(), $resourceClass);
    }

    /**
     * Stores an api resource to an existing Eloquent model instance.
     *
     * @param $resource
     * @param $id
     * @param array $context
     * @return array|mixed|object
     */
    public function persistExisting($resource, $id, array $context = [])
    {
        $this->gate->authorize('put', $resource);
        $resourceClass = get_class($resource);
        $modelClass = $this->getModelClass($resourceClass, $context);
        $modelInstance = $modelClass::where(['id' => $id])->firstOrFail();
        $array = $this->normalizer->normalize($resource);
        if (!is_array($array)) {
            throw new UnexpectedValueException('Resource ' . get_class($resource) . ' was normalized to a non array field');
        }
        unset($array['id']);
        $modelInstance->unguard();
        try {
            $modelInstance->fill($array);
        } finally {
            $modelInstance->reguard();
        }
        $modelInstance->save();

        return $this->denormalize($modelInstance->toArray(), $resourceClass);
    }

    /**
     * Removes a resource from the database.
     *
     * @param string $resourceClass
     * @param $id
     * @param array $context
     */
    public function remove(string $resourceClass, $id, array $context)
    {
        $modelClass = $this->getModelClass($resourceClass, $context);
        $modelInstance = $modelClass::where($context[1] ?? $context['id'] ?? 'id', $id)->first();
        if (!$modelInstance) {
            return;
        }
        $result = $this->denormalize($modelInstance->toArray(), $resourceClass);
        $this->gate->authorize('delete', $result);
        $modelClass::destroy($id);
    }

    /**
     * Denormalizes from an array to an api resource with the EvilReflectionPropertyNormalizer active, so a domain
     * with only a getter will be hydrated correctly.
     *
     * @param array $array
     * @param string $resourceClass
     * @return array|object
     */
    private function denormalize(array $array, string $resourceClass)
    {
        ContextualNormalizer::enableDenormalizer(EvilReflectionPropertyNormalizer::class);
        try {
            $res = $this->denormalizer->denormalize($array, $resourceClass, null, []);
        } finally {
            ContextualNormalizer::disableDenormalizer(EvilReflectionPropertyNormalizer::class);
        }

        return $res;
    }

    /**
     * Returns the name of the model class associated to a api resource class.
     *
     * @param string $resourceClass
     * @return string
     */
    private function determineModel(string $resourceClass): string
    {
        return str_replace('\\ApiResources\\', '\\Models\\', $resourceClass);
    }

    /**
     * Returns the name of the model class associated to a api resource class and a context.
     *
     * @param string $resourceClass
     * @param array $context
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
}
