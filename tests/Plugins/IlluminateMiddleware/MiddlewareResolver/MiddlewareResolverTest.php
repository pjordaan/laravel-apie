<?php

namespace W2w\Laravel\Apie\Tests\Plugins\IlluminateMiddleware\MiddlewareResolver;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Orchestra\Testbench\Http\Middleware\RedirectIfAuthenticated;
use PHPUnit\Framework\TestCase;
use W2w\Laravel\Apie\Plugins\IlluminateMiddleware\MiddlewareResolver\MiddlewareResolver;

class MiddlewareResolverTest extends TestCase
{
    /**
     * @dataProvider resolveMiddlewareProvider
     */
    public function testResolveMiddleware(array $expectedMiddleware, Router $router, array $middleware)
    {
        $container = new Container();
        $container->instance('router', $router);
        $testItem = new MiddlewareResolver($container);
        $this->assertEquals(
            $expectedMiddleware,
            iterator_to_array($testItem->resolveMiddleware($middleware))
        );
    }

    public function resolveMiddlewareProvider()
    {
        $container = new Container();
        $router = new Router(new Dispatcher($container), $container);
        $router->middlewareGroup(
            'web',
            [
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
            ]
        );
        $router->middlewareGroup(
            'api',
            [
                'throttle:60,1',
                'bindings',
            ]
        );
        $aliases = [
            'auth' => Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'bindings' => SubstituteBindings::class,
            'cache.headers' => SetCacheHeaders::class,
            'can' => Authorize::class,
            'guest' => RedirectIfAuthenticated::class,
            'signed' => ValidateSignature::class,
            'throttle' => ThrottleRequests::class,
        ];
        foreach ($aliases as $alias => $className) {
            $router->aliasMiddleware($alias, $className);
        }
        yield [[], $router, []];
        yield [[Authenticate::class], $router, ['auth:api']];
    }
}
