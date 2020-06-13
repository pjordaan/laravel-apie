## Adding event hooks for Laravel/Lumen
The event hooks are only added if the IlluminateDispatcherPlugin is added an Apie plugin in the config:
```php
<?php
// config/apie.php
use W2w\Laravel\Apie\Plugins\IlluminateDispatcher\IlluminateDispatcherPlugin;

return [
    'plugins' => [IlluminateDispatcherPlugin::class],
];
```
If you used the vendor:publish artisan command it will be enabled by default.
Once enabled Apie connects to the event disaptcher and the policies.

## Laravel validation
Apie will be linked to the [Laravel validators](https://laravel.com/docs/6.x/validation)
To add validation rules you require to add an interface to your Api resource.
The validation is done before the class instance is created.
The generated OpenAPI spec is not checking validation rules to change
the schema of the object.
```php
<?php
use W2w\Laravel\Apie\Contracts\HasApieRulesContract;

class Example implements HasApieRulesContract
{
    public $one;

    public $two;

    /**
     * Returns a list of validation rules just like a laravel form request works.
     *
     * @return array
     */
    public static function getApieRules(): array
    {
        return [
            'one' => ['required', 'numeric'],
            'two' => ['required', 'numeric'],
        ];
    }
}
```

## Policies integration
It's possible to use the [Laravel policies](https://laravel.com/docs/6.x/authorization#creating-policies) to
return authorization exceptions.

```php
<?php
class ExamplePolicy
{
    public function create($user, Example $object)
    {
        // code to authorize that this object can be created as a new resource(POST).
    }

    public function update($user, Example $object)
    {
        // code to authorize that this object can update an existing resource(PUT).
    }

    public function view($user, Example $object)
    {
        // code to authorize if the object can be returned with GET. 
        // this code is also used for getting multiple resources.
    }

    public function remove($user, $id)
    {
        // code to authorize if the object can be deleted with DELETE.
    }
}
```

## Events
Apie has lifecycle events added. They are also hooked to Laravel with the IlluminateDispatcherPlugin.
These events are triggered

- __apie.pre_create_resource, apie.post_create_resource__: POST new resource request
- __apie.pre_modify_resource, apie.post_modify_resource__: PUT existing resource request
- __apie.pre_decode_request_body, apie.post_decode_request_body__: decode request body POST/PUT
- __apie.pre_retrieve_resource, apie.post_retrieve_resource__: GET single resource request
- __apie.pre_retrieve_all_resources, apie.post_retrieve_all_resources__: GET multiple resources request
- __apie.pre_persist_new_resource, apie.post_persist_new_resource__: calling the persister for a POST
- __apie.pre_persist_existing_resource, apie.post_persist_existing_resource__: calling the persister for a PUT
- __apie.pre_delete_resource, apie.post_delete_resource__: calling the persister for a DELETE
All the events can even take over the Apie functionality even though this is highly not recommended.

## Api resource persisters and retrievers
From version 3.3 persisters and retrievers can be auto-wired with Laravel and be used in Apie automatically.
Before version 3.3 or if an api resource is configured with annotations, they have to be registered manually
in a service provider:
```php
use Illuminate\Support\ServiceProvider;

class ExampleServiceProvider extends ServiceProvider
{
    public function register()
    {
        // register the service manually and Apie will always find it...
        $this->app->singleton(SomePersister::class);
    }
}
```
