## Writing your own controllers
In case you want a different logic for the REST API action or you want to link to a different service, for example
Inertia.js that is easily possible with auto-wiring in Laravel. All you have to do is make sure the option  
'bind-api-resource-facade-response' is true (this is on by default). The example below uses the parsing of Apie
but links the data to [Inertia.js](https://inertiajs.com/) instead.

```php
<?php
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Inertia\Inertia;
use W2w\Lib\Apie\Core\Models\ApiResourceFacadeResponse;
use Psr\Http\Message\ServerRequestInterface;

class InertiaController extends BaseController
{
    public function view(ServerRequestInterface $request, ApiResourceFacadeResponse $response)
    {
       return Inertia::render('View/' . ucfirst($request->getMethod()), [
           'resource' => $request->getAttribute('resource'),
           'id' => $request->getAttribute('id'),
           'data' => $response->getNormalizedData(),
       ]); 
    }
    
    public function viewAll(ServerRequestInterface $request, ApiResourceFacadeResponse $response)
    {
        return Inertia::render('View/All', [
            'resource' => $request->getAttribute('resource'),
            'data' => $response->getNormalizedData(),
        ]); 
    }
    
    public function modify(
        Request $request,
        ApiResourceFacadeResponse $response
    ) {
        $request->session()->flash('message', get_class($response->getResource()) . ' stored successfully');
        return redirect()->route('apie.inertia.all', ['resource' => $request->getAttribute('resource')]);
    }
}
```

```php
<?php
// in your routes/route.php somewhere:

Route::post('/{resource}/', 'InertiaController@modify')->name('apie.inertia.post');
Route::put('/{resource}/{id}', 'InertiaController@modify')->name('apie.inertia.put');
Route::get('/{resource}/', 'InertiaController@all')->name('apie.inertia.all');
Route::get('/{resource}/{id}', 'InertiaController@view')->name('apie.inertia.get');
Route::delete('/{resource}/{id}', 'InertiaController@modify')->name('apie.inertia.delete');
```

That's all!

## Typehint api resources
You can also typehint api resource classes. Any Apie action, except the call to retrieve all objects can be typehinted in the controller that way.
You require an id placeholder or a default for 'id' to make it work.


```php
<?php
use Illuminate\Routing\Controller as BaseController;
use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;

class ExampleController extends BaseController
{
    public function displayApplicationName(ApplicationInfo $applicationInfo)
    {
        return $applicationInfo->getAppName();
    }
}
```

```php
<?php
// in your routes/route.php somewhere:
Route::get('/{resource}/{id}', 'ExampleController@displayApplicationName');
Route::get('/{resource}', 'ExampleController@displayApplicationName')->defaults('id', 1);
```
## Versioned REST API's.
If you want to make it link to a specific rest api because of [versioning](07-versioning.md), you need to add another default to the route to pick the correct rest api:
```php
<?php
// in your routes/route.php somewhere:
Route::get('/{resource}/', 'InertiaController@all')->name('apie.inertia.all')->defaults('context', ['v2']);
```

If you want to typehint api resources that are only available in a specific REST API you need to use the [AbstractRestApiServiceProvider solution](07-versioning.md#add-rest-apis-with-service-provider) 
or else you can not use it as typehint in your controllers.
