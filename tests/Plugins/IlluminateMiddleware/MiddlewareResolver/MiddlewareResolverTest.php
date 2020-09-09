<?php

namespace W2w\Laravel\Apie\Tests\Plugins\IlluminateMiddleware\MiddlewareResolver;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;
use W2w\Laravel\Apie\Plugins\IlluminateMiddleware\MiddlewareResolver\MiddlewareResolver;
use W2w\Laravel\Apie\Tests\Mocks\MockKernel;

class MiddlewareResolverTest extends TestCase
{
    /**
     * @dataProvider resolveMiddlewareProvider
     */
    public function testResolveMiddleware(array $expectedMiddleware, Kernel $kernel, array $middleware)
    {
        $container = new Container();
        $testItem = new MiddlewareResolver($kernel, $container);
        $this->assertEquals(
            $expectedMiddleware,
            iterator_to_array($testItem->resolveMiddleware($middleware))
        );
    }

    public function resolveMiddlewareProvider()
    {
        $application = $this->prophesize(Application::class);
        $router = $this->prophesize(Router::class);
        $kernel = new MockKernel($application->reveal(), $router->reveal());
        yield [$kernel->getDefaultMiddleware(), $kernel, []];
        $expected = $kernel->getDefaultMiddleware();
        $expected[] = Authenticate::class;
        yield [$expected, $kernel, ['auth:api']];
    }
}
