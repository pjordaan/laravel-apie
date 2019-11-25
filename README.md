# laravel-apie
Laravel wrapper for the apie library
[![CircleCI](https://circleci.com/gh/pjordaan/laravel-apie.svg?style=svg)](https://circleci.com/gh/pjordaan/laravel-apie)
[![codecov](https://codecov.io/gh/pjordaan/laravel-apie/branch/master/graph/badge.svg)](https://codecov.io/gh/pjordaan/laravel-apie/)
[![Travis](https://api.travis-ci.org/pjordaan/laravel-apie.svg?branch=master)](https://travis-ci.org/pjordaan/laravel-apie)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/pjordaan/laravel-apie/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/pjordaan/laravel-apie/?branch=master)

## What does it do
This is a small wrapper around the library w2w/apie for Laravel. This library maps simple POPO's (Plain Old PHP Objects) to REST api calls. It is very similar to the excellent api platform library.

It also adds a class EloquentModelDataLayer to persist and retrieve api resources as Eloquent models and adds a status check to see if it can connect with the database. See the documentation of apie at https://github.com/pjordaan/apie

## Installation
In your Laravel package you should do the usual steps to install a Laravel package.
```bash
composer require w2w/laravel-apie
```
In case you have no autodiscovery on or your Laravel version is too low, you require to add W2w\Laravel\Apie\Providers\ApiResourceServiceProvider::class to your list of service providers manually.

Publish the api resource config with artisan publish to get a config/apie.php file and run migrations to get the database.
```bash
artisan publish
artisan migrate
```

Now visit /swagger-ui to see the generated OpenApi spec. It will only contain specs for the default installed api resources, which is a check to identify your REST API and a health check resource. It will check if it can connect to the database.

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
Now in config/apie.php we should add the class to add it to the api resources:
```php
<?php
//config/apie.php
use App\ApiResources\SumExample;
use W2w\Lib\Apie\ApiResources\ApplicationInfo;
use W2w\Lib\Apie\ApiResources\Status;

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
## Display a swagger UI testpage.
In case you want to have more functionality I advise you to look at the laravel package darkaonline/l5-swagger, but this
Laravel package contains a simple Swagger UI page to test/view your REST api calls in the browser.
All you have to do is open config/apie.php and fill in the url of the page:
```php
<?php
//config/apie.php
return [
    'swagger-ui-test-page'      => '/swagger-ui',
];
````

By default the test page is found on /swagger-ui. 

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
use W2w\Lib\Apie\Resources\ApiResourcesFromNamespace;

return [
    'resources' => ApiResourcesFromNamespace::createApiResources('App\RestApi\ApiResources'),
];
```
Now if I put a class inside the namespace App\RestApi\ApiResources, the class will be registered for Apie.

## Integrate with l5-swagger
There is a laravel package called darkaonline/l5-swagger that is created to display a swagger ui page that shows the REST API in a browser-friendly interface. With a little bit of tweaking it is possible to use this package to show the OpenAPI spec created by this tool.

- Follow the steps at https://github.com/DarkaOnLine/L5-Swagger to install the library.
- Publish the config and change the url in the config to a different url to avoid a route conflict.
- Go to the page generated by the library and in the input field at the top replace the path with /api/doc.json and click 'explore'

It is possible to modify the template to always show the documentation.
- Run artisan publish to publish the views of l5-swagger.
- Open file resources/views/vendor/l5-swagger/index.blade.php
- Replace this part of code the url definition:
```javascript
const ui = SwaggerUIBundle({
    dom_id: '#swagger-ui',

    url: "{!! route('apie.docs') !!}",
```
    
Now if you refresh you will see your REST API right away.
![screenshot](https://github.com/pjordaan/laravel-apie/blob/master/docs/l5swagger-screenshot.png?raw=true)
