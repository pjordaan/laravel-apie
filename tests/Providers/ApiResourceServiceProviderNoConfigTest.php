<?php
namespace W2w\Laravel\Apie\Tests\Providers;

use erasys\OpenApi\Spec\v3\Server;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;
use W2w\Lib\Apie\ApiResourceFacade;
use W2w\Lib\Apie\ApiResources\App;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSpecGenerator;

class ApiResourceServiceProviderNoConfigTest extends AbstractLaravelTestCase
{
    public function testApiResourceFacade_works_without_config()
    {
        /**
 * @var ApiResourceFacade $class 
*/
        $class = $this->app->get(ApiResourceFacade::class);
        $this->assertInstanceOf(ApiResourceFacade::class, $class);
        $appResponse = $class->get(App::class, 'name', null);
        /**
 * @var App $resource 
*/
        $resource = $appResponse->getResource();
        $hash = include __DIR__ . '/../../config/apie.php';
        $expected = new App(
            'Laravel',
            'testing',
            $hash['metadata']['hash'],
            false
        );
        $this->assertEquals($expected, $resource);
    }

    public function testOpenApiSchema_works_without_config()
    {
        /**
 * @var OpenApiSpecGenerator $class 
*/
        $class = $this->app->get(OpenApiSpecGenerator::class);
        $this->assertInstanceOf(OpenApiSpecGenerator::class, $class);
        $spec = $class->getOpenApiSpec();
        $this->assertCount(1, $spec->servers);
        $expected = new Server(
            'http://localhost/api',
            null,
            null
        );
        $this->assertEquals($expected, reset($spec->servers));
    }
}
