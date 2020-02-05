## Eloquent model data layer
laravel-apie adds an EloquentDataLayer class to persist and retrieve objects with an eloquent model:

The simplest setup:
```php
<?php
namespace App\ApiResources;

use W2w\Laravel\Apie\Plugins\Illuminate\DataLayers\EloquentModelDataLayer;
use W2w\Lib\Apie\Annotations\ApiResource;

/**
 * @ApiResource({
*     persistClass=EloquentModelDataLayer::class,
*     retrieveClass=EloquentModelDataLayer::class
*  )
 */
class Example {
    private $id;
    
    /**
     * @var string 
     */
    public $field;
    
    public function __construct(?int $id)
    {
        $this->id = $id;
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }
}
```

With this setup it will look for an eloquent model App\Models\Example or App\Enities\Example and with apie it will create/update
the id and field field.

If you want to specify the Eloquent model to be used, you need to configure this.
```php
<?php
namespace App\ApiResources;

use W2w\Laravel\Apie\Plugins\Illuminate\DataLayers\EloquentModelDataLayer;
use W2w\Lib\Apie\Annotations\ApiResource;
use Models\Example as ExampleModel;

/**
 * @ApiResource({
 *     persistClass=EloquentModelDataLayer::class,
 *     retrieveClass=EloquentModelDataLayer::class,
 *     context={
 *         "model": ExampleModel::class
 *     }
 * )
 */
class Example {
    private $id;
    
    /**
     * @var string 
     */
    public $field;
    
    public function __construct(?int $id)
    {
        $this->id = $id;
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }
}
```
In the example Models\Example is used as Eloquent Model class.

## Search filters
Search filters are done the same way as the core data layers in w2w/apie.
See the [apie docs](https://github.com/pjordaan/apie/blob/master/docs/04-search-filters.md) how to add search filters.

## Use the Eloquent serialization in your own data layer.
EloquentModelDataLayer can only do simple transformations. In case you want to customize it's better to make your own 
data layer and use the same serialization logic used by EloquentModelDataLayer. EloquentModelDataLayer uses internally
the EloquentModelSerializer. You can use that in your own data layers.

```php
<?php
use Illuminate\Database\Eloquent\ModelNotFoundException;
use W2w\Lib\Apie\Core\SearchFilters\SearchFilterRequest;
use W2w\Lib\Apie\Exceptions\ResourceNotFoundException;
use W2w\Lib\Apie\Interfaces\ApiResourceRetrieverInterface;

class SubscriptionDataLayer implements ApiResourceRetrieverInterface
{
    private $subscriptionRepository;

    private $serializer;

    public function __construct(SubscriptionRepository $subscriptionRepository, EloquentModelSerializer $serializer)
    {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function retrieve(string $resourceClass, $id, array $context)
    {
        try {
            $modelInstance = $this->subscriptionService->createFindQueryForCurrentUser()->where('uuid', $id)->firstOrFail();
        } catch (ModelNotFoundException $notFoundException) {
            throw new ResourceNotFoundException($id);
        }
        $result = $this->serializer->toResource($modelInstance, $resourceClass);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveAll(
        string $resourceClass,
        array $context,
        SearchFilterRequest $searchFilterRequest
    ): iterable {

        return $this->serializer->toList(
            $this->subscriptionService->createFindQueryForCurrentUser(),
            $resourceClass,
            $searchFilterRequest
        );
    }
}
```

## Mapping raw queries to an Apie object
Next to the EloquentModelDataLayer laravel-apie also adds a DatabaseQueryRetriever object. This class can only be used for
retrieving and maps the results of a raw sql query to an API resource.

```php
<?php
use W2w\Laravel\Apie\Plugins\Illuminate\DataLayers\DatabaseQueryRetriever;
use W2w\Lib\Apie\Annotations\ApiResource;

/**
 * @ApiResource(
 *     retrieveClass=DatabaseQueryRetriever::class,
 *     context={
 *         "query_file": "../resources/stats.sql"
 *     }
 * )
 */
class Stats
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
```
The path is relative from the file where the Api resource class is found.

A stats.sql file could be something like this:
```sql
SELECT `group` as `id`, SUM(LENGTH(size)) as `size`, COUNT(id) as `count`
FROM log_items
GROUP BY `group` 
```

This one is often used in combination with statistics and logging.
