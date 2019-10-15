<?php
namespace W2w\Laravel\Apie\Tests\Providers;

use erasys\OpenApi\Spec\v3\Server;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;
use W2w\Laravel\Apie\Tests\Mocks\DomainObjectForFileStorage;
use W2w\Lib\Apie\ApiResourceFacade;
use W2w\Lib\Apie\ApiResources\App;
use W2w\Lib\Apie\ApiResources\Status;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSpecGenerator;

class ApiResourceServiceProviderTest extends AbstractLaravelTestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = $app->make('config');
        // Setup default database to use sqlite :memory:
        $config->set('database.default', 'testbench');
        $config->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $config->set(
            'api-resource',
            [
                'resources' => [App::class, Status::class, DomainObjectForFileStorage::class],
                'metadata'               => [
                    'title'            => 'Laravel REST api',
                    'version'          => '1.0',
                    'hash'             => '12345',
                    'description'      => 'OpenApi description',
                    'terms-of-service' => '',
                    'license'          => 'Apache 2.0',
                    'license-url'      => 'https://www.apache.org/licenses/LICENSE-2.0.html',
                    'contact-name'     => 'contact name',
                    'contact-url'      => 'example.com',
                    'contact-email'    => 'admin@example.com',
                ]
            ]
        );
    }

    public function testApiResourceFacade()
    {
        /** @var ApiResourceFacade $class */
        $class = $this->app->get(ApiResourceFacade::class);
        $this->assertInstanceOf(ApiResourceFacade::class, $class);
        $appResponse = $class->get(App::class, 'name', null);
        /** @var App $resource */
        $resource = $appResponse->getResource();
        $expected = new App(
            'Laravel',
            'testing',
            '12345',
            false
        );
        $this->assertEquals($expected, $resource);
    }

    public function testOpenApiSchema()
    {
        /** @var OpenApiSpecGenerator $class */
        $class = $this->app->get(OpenApiSpecGenerator::class);
        $this->assertInstanceOf(OpenApiSpecGenerator::class, $class);
        $spec = $class->getOpenApiSpec();
        $this->assertEquals($spec->info->description, 'OpenApi description');
        $this->assertEquals($spec->info->contact->email, 'admin@example.com');
        $this->assertEquals($spec->info->contact->url, 'example.com');
        $this->assertCount(1, $spec->servers);
        $expected = new Server(
            'http://localhost/api',
            null,
            null
        );
        $this->assertEquals($expected, reset($spec->servers));
    }
}
