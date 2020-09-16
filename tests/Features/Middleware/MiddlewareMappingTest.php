<?php

namespace W2w\Laravel\Apie\Tests\Features\Middleware;

use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Http\Middleware\SetCacheHeaders;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;

class MiddlewareMappingTest extends AbstractLaravelTestCase
{
    protected function getEnvironmentSetUp($application)
    {
        $config = $application['config'];
        $config->set('apie.apie-middleware', ['throttle:80,1', AuthenticateWithBasicAuth::class, SetCacheHeaders::class]);
        parent::getEnvironmentSetUp($application);
    }

    public function test_openapi_spec()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/api/doc.yml');
        file_put_contents(__DIR__ . '/doc.yml', $response->getContent());
        $response->assertSeeText(429);
        $response->assertSeeText('x-RateLimit-Limit');
        $response->assertSeeText('etag');
        $response->assertSeeText('md5');
    }
}
