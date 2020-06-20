# laravel-apie
Laravel wrapper for the apie library
[![CircleCI](https://circleci.com/gh/pjordaan/laravel-apie.svg?style=svg)](https://circleci.com/gh/pjordaan/laravel-apie)
[![codecov](https://codecov.io/gh/pjordaan/laravel-apie/branch/master/graph/badge.svg)](https://codecov.io/gh/pjordaan/laravel-apie/)
[![Travis](https://api.travis-ci.org/pjordaan/laravel-apie.svg?branch=master)](https://travis-ci.org/pjordaan/laravel-apie)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/pjordaan/laravel-apie/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/pjordaan/laravel-apie/?branch=master)

## What does it do
This is a small wrapper around the library [w2w/apie](https://github.com/pjordaan/apie) for Laravel. This library maps simple POPO's (Plain Old PHP Objects) to REST api calls. It is very similar to the excellent api platform library, but then for Laravel.

It also adds a class EloquentModelDataLayer to persist and retrieve api resources as Eloquent models and adds a status check to see if it can connect with the database. See the documentation of apie at https://github.com/pjordaan/apie

## Forwards compatiblity Apie version 4
By default laravel-apie will still use the old 3.* serialization.
In Apie version 4 this will change drastically, so a config option
is added to enable the forwards compatible 4.* release.

## Contents
1. [Installation](#Installation)
2. [Lumen integration](docs/02-lumen-integration.md)
3. [Adding a a new api resource](#adding-a-new-api-resource)
4. [Automate registering classes](#automate-registering-api-resources)
5. [Hooking in the laravel/lumen error handler](docs/05-error-handler.md)
6. [Optimizations for production](docs/06-optimizations.md)
7. [Versioning](docs/07-versioning.md)
8. [Integrate Eloquent with Apie](docs/08-eloquent-data-layer.md)
9. [Custom normalizers/value objects](docs/09-custom-normalizers.md)
10. [Modifying OpenAPI spec](docs/10-modifying-openapi-spec.md)
11. [Use your own controllers](docs/11-own-controllers.md)
12. [Resource sub actions](docs/12-sub-actions.md)
13. [Laravel components integration](docs/13-laravel-component-integrations.md)
14. [PSR6 Cache integration](docs/14-cache-integration.md)
15. [L5-swagger integration](docs/15-l5swagger-integration.md)

## Installation
In your Laravel package you should do the usual steps to install a Laravel package.
```bash
composer require w2w/laravel-apie
```
In case you have no autodiscovery on to add W2w\Laravel\Apie\Providers\ApiResourceServiceProvider::class to your list of service providers manually.

Afterwards run the commands to publish the config to apie.php and run the migrations for the status checks.
```bash
artisan vendor:publish --provider="W2w\Laravel\Apie\Providers\ApiResourceServiceProvider"
artisan migrate
```

Now visit /swagger-ui to see the generated OpenApi spec. It will only contain specs for the default installed api resources, which is a check to identify your REST API and a health check resource. It will check if it can connect to the database.

## Adding a new api resource
create this class in your app/ApiResources:
```php
<?php
namespace App\RestApi\ApiResources;

use W2w\Lib\Apie\Annotations\ApiResource;
use W2w\Lib\Apie\Plugins\Core\DataLayers\NullDataLayer;

/**
 * @ApiResource(disabledMethods={"get"}, persistClass=NullDataLayer::class)
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
Now in config/apie.php we should add the class to add it to the api resources:
```php
<?php
//config/apie.php
use App\ApiResources\SumExample;
use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;
use W2w\Lib\Apie\Plugins\StatusCheck\ApiResources\Status;

return [
'resources' => [ApplicationInfo::class, Status::class, SumExample::class]
];
```

If you refresh /api/doc.json you can see you get an extra POST call to create a SumExample resource. With any OpenApi tool or with Postman you can test the POST command. If you would make a POST call to /api/sum_example with body
```
{
  "one": 13,
  "two": 1
}
```
You would get:
```
{
  "one": 13,
  "two": 1,
  "addition": 14,
  "subtraction": 12,
  "multiplication": 13,
  "divison": 13
}
```

## Automate registering api resources.
It is possible to automate registering api resources without having to manually update the resources list in config/apie.php
We can auto-register all classes in a specific namespace with this:

- In a terminal run:
```bash
composer require haydenpierce/class-finder
```
- Open config/apie.php
- Edit the file like this:
```php
<?php
//config/apie.php
use W2w\Lib\Apie\Core\Resources\ApiResourcesFromNamespace;

return [
    'resources' => ApiResourcesFromNamespace::createApiResources('App\RestApi\ApiResources'),
];
```
Now if I put a class inside the namespace App\RestApi\ApiResources, the class will be registered for Apie.

Make sure that for production you use laravel's config cache to reduce load on your server.

