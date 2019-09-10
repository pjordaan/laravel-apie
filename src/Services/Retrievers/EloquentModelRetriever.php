<?php

namespace W2w\Laravel\Apie\Services\Retrievers;

use W2w\Lib\Apie\Normalizers\ContextualNormalizer;
use W2w\Lib\Apie\Normalizers\EvilReflectionPropertyNormalizer;
use W2w\Lib\Apie\Persisters\ApiResourcePersisterInterface;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EloquentModelRetriever implements ApiResourceRetrieverInterface, ApiResourcePersisterInterface
{
    private $normalizer;

    private $denormalizer;

    private $gate;

    public function __construct(NormalizerInterface $normalizer, DenormalizerInterface $denormalizer, Gate $gate)
    {
        $this->normalizer = $normalizer;
        $this->denormalizer = $denormalizer;
        $this->gate = $gate;
    }

    public function retrieve(string $resourceClass, $id, array $context)
    {
        $modelClass = $this->getModelClass($resourceClass, $context);
        $modelInstance = $modelClass::where($context[1] ?? $context['id'] ?? 'id', $id)->firstOrFail();
        $result = $this->denormalize($modelInstance->toArray(), $resourceClass);
        $this->gate->authorize('get', $result);

        return $result;
    }

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

    public function persistNew($resource, array $context = [])
    {
        $this->gate->authorize('post', $resource);
        $resourceClass = get_class($resource);
        $array = $this->normalizer->normalize($resource);
        if (!is_array($array)) {
            throw new RuntimeException('Resource ' . get_class($resource) . ' was normalized to a non array field');
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

    public function persistExisting($resource, $id, array $context = [])
    {
        $this->gate->authorize('put', $resource);
        $resourceClass = get_class($resource);
        $modelClass = $this->getModelClass($resourceClass, $context);
        $modelInstance = $modelClass::where(['id' => $id])->firstOrFail();
        $array = $this->normalizer->normalize($resource);
        if (!is_array($array)) {
            throw new RuntimeException('Resource ' . get_class($resource) . ' was normalized to a non array field');
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

    private function determineModel(string $resourceClass): string
    {
        return str_replace('\\ApiResources\\', '\\Models\\', $resourceClass);
    }

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
