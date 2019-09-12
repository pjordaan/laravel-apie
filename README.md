# laravel-apie
Laravel wrapper for the apie library

## What does it do
This is a small wrapper around the library w2w/apie for Laravel. This library maps simple POPO's (Plain Old PHP Objects) to REST api calls. It is very similar to the excellent api platform library.

It also adds a class EloquentModelRetriever to persist and retrieve api resources as Eloquent models and adds a status check to see if it can connect with the database. See the documentation of apie at https://github.com/pieterw2w/apie

## Installation
In your Laravel package you should do the usual steps to install a Laravel package.
```bash
composer require w2w/laravel-apie
```
In case you have no autodiscovery on or your Laravel version is too low, you require to add W2w\Laravel\Apie\Providers\ApiResourceServiceProvider::class to your list of service providers manually.

Publish the api resource config with artisan publish to get a config/api-resource.php file and run migrations to get the database.
```bash
artisan publish
artisan migrate
```

Now visit /api/doc.json to see the generated OpenApi spec. It will only contain specs for the default installed api resources, which is a check to identify your REST API and a health check resource. It will check if it can connect to the database.

## Adding a new api resource
create this class in your app/ApiResources:
```php
<?php
namespace App\RestApi\ApiResources;

use W2w\Lib\Apie\Annotations\ApiResource;
use W2w\Lib\Apie\Persisters\NullPersister;

/**
 * @ApiResource(disabledMethods={"get"}, persistClass=NullPersister::class)
 */
class SumExample
{
    private $one;

    private $two;

    public function __construct(float $one, float $two)
    {
        $this->one = $one;
        $this->two = $two;
    }

    public function getOne(): float
    {
        return $this->one;
    }

    public function getTwo(): float
    {
        return $this->two;
    }

    public function getAddition(): float
    {
        return $this->one + $this->two;
    }

    public function getSubtraction(): float
    {
        return $this->one - $this->two;
    }

    public function getMultiplication(): float
    {
        return $this->one * $this->two;
    }

    public function getDivison(): ?float
    {
        // === and == can fail because of floating points....
        if (abs($this->two) < 0.000001) {
            return null;
        }
        return $this->one / $this->two;
    }
}
```
Now in config/api-resources.php we should add the class to add it to the api resources:
```php
use App\ApiResources\SumExample;
use W2w\Lib\Apie\ApiResources\App;
use W2w\Lib\Apie\ApiResources\Status;

//config/api-resources.php
return [
'resources' => [App:class, Status::class, SumExample::class]
];
```
