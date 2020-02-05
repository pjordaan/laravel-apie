## Modifying the OpenAPI spec.
The OpenAPI spec is generated in a specific format. Sometimes you want to add/modify some changes, for example you
require to login before you can make the call or you want to change the tags of the api calls.
If the OpenAPI spec is generated an event is dispatched in laravel and you listen to it to modify the spec. Internally
Apie uses  the library [erasys/openapi-php](https://github.com/erasys/openapi-php) to create the OpenAPI spec.

We create an event subscriber for the event. In this example we modify the tags and we add security scheme.
In a future build we will try to automatically add specific OpenAPI specs depending on the middleware being used.

```php
<?php

use erasys\OpenApi\Spec\v3\Operation;
use erasys\OpenApi\Spec\v3\SecurityScheme;
use Illuminate\Contracts\Events\Dispatcher;
use W2w\Laravel\Apie\Events\OpenApiSpecGenerated;

/**
 * Subscriber that listens to even to change the OpenAPI spec.
 */
class OpenApiSpecSubscriber
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(OpenApiSpecGenerated::class, __CLASS__ . '@addAuthorizationScheme');
        $events->listen(OpenApiSpecGenerated::class, __CLASS__ . '@modifyTagActions');
    }

    /**
     * Example where security scheme is added. 
     */
    public function addAuthorizationScheme(OpenApiSpecGenerated $event)
    {
        $doc = $event->getDocument();
        $doc->components->securitySchemes['bearerAuth'] = new SecurityScheme(
            'http',
            'This token is retrieved by the /login call',
            [
                'scheme'       => 'bearer',
                'bearerFormat' => 'JWT',
            ]
        );
        // mark login token specifically to have no authorization required.
        if (!empty($doc->paths['/login'])) {
            $doc->paths['/login']->post->security = [];
        }

        $doc->security[] = ['bearerAuth' => []];
    }

    /**
     * Override all tags.
     *
     * @param OpenApiSpecGenerated $event
     */
    public function tagActions(OpenApiSpecGenerated $event)
    {
        $doc = $event->getDocument();
        foreach ($doc->paths as $path => $pathItem) {
            $this->patchOperation($path, $pathItem->post);
            $this->patchOperation($path, $pathItem->get);
            $this->patchOperation($path, $pathItem->delete);
            $this->patchOperation($path, $pathItem->patch);
            $this->patchOperation($path, $pathItem->head);
            $this->patchOperation($path, $pathItem->put);
            $this->patchOperation($path, $pathItem->trace);
        }
    }

    /**
     * Patch openapi operations tags
     */
    private function patchOperation(string $path, ?Operation $operation)
    {
        if (is_null($operation)) {
            return;
        }
        static $mapping = [
            'login'            => 'auth',
            'customer'         => 'subscription',
            'application_info' => 'admin',
            'status'           => 'admin',
        ];
        foreach ($mapping as $searchString => $wantedTag) {
            if (strpos($path, $searchString) !== false) {
                $operation->tags = [$wantedTag];

                return;
            }
        }
    }
}
```

We need a service provider to register it:
```php
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventProvider extends ServiceProvider
{
    protected $subscribe = [OpenApiSpecSubscriber::class];
}

```
