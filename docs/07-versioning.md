## Versioning
Good REST API's want to have versioning or maybe you want to have multiple REST API's.

For that reason we added support to have multiple REST API's

You can make a REST API that inherits the configuration of the parent Apie in config/apie.php.

For example you would create a versioned api like this:

```php
<?php
//config/apie.php
use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;
use W2w\Lib\Apie\Plugins\StatusCheck\ApiResources\Status;

return [
    // these resources are available in v1 and v2.
    'resources' => [
        ApplicationInfo::class, Status::class,
    ],
    'api-url' => false,
    'swagger-ui-test-page' => '',
    'contexts' => [
        'v1' => [
            'resources' => [
                \App\ApiResources\V1\Class1::class,
                \App\ApiResources\V1\Class2::class
            ],
            'api-url'              => '/api/v1',
            'swagger-ui-test-page' => '/swagger-ui/v1',
        ],
        'v2' => [
            'resources'            => [
                \App\ApiResources\V2\Class1::class,
                \App\ApiResources\V2\Class2::class
            ],
            // this plugin is only used in version 2:
            'plugins'              => [ApieDomainPlugin::class],
            'api-url'              => '/api/v2',
            'swagger-ui-test-page' => '/swagger-ui/v2',
        ],
    ],
];
```

Routes are created with names like 'apie.v1.post' and 'apie.v1.docs', etc.

## Add REST API's with service provider.
It is also possible to add a REST api with a specific service provider instead. The benefit is that you can also integrate
the parsing in your own controllers with the bind-api-resource-facade-response option. It can also be used very well
in combination with [Laravel modules](https://github.com/nWidart/laravel-modules).

```php
<?php
use W2w\Laravel\Apie\Providers\AbstractRestApiServiceProvider;

class AddedAnotherRestApiServiceProvider extends AbstractRestApiServiceProvider {
    protected function getApiName(): string
    {
        return 'another-api-v1';
    }
    
    protected function getApiConfig(): array
    {
        return [
            'resources' => [
                \App\AnotherApi\V1\Class1::class,
                \App\AnotherApi\V1\Class2::class,
            ],
            'api-url'              => '/another-api',
            'swagger-ui-test-page' => '/swagger-ui/another-api',
        ];
    }
}
```
