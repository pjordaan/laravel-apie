<?php

namespace W2w\Laravel\Apie\Tests\Features\Middleware;

use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Foundation\Application;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;

class MiddlewareMappingTest extends AbstractLaravelTestCase
{
    protected function setUpDatabase(Application $application, string $db = ':memory:'): void
    {
        $config = $application['config'];
        $config->set('database.default', 'testbench');
        $config->set(
            'database.connections.testbench',
            [
                'driver'   => 'sqlite',
                'database' => $db,
                'prefix'   => '',
            ]
        );
        var_dump(__METHOD__);
        $config->set('apie.apie-middleware', ['throttle:80,1', AuthenticateWithBasicAuth::class]);
    }

    public function test_openapi_spec()
    {var_dump(__METHOD__);
        $this->withoutExceptionHandling();
        $response = $this->get('/api/doc.yml');
        $response->assertSeeText(429);
        $response->assertSeeText('X-RateLimit-Limit');
    }
}
