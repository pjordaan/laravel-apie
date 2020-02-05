<?php
namespace W2w\Laravel\Apie\Tests\Providers;

use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;
use W2w\Laravel\Apie\Tests\Mocks\DomainObjectForFileStorage;
use W2w\Lib\Apie\Core\ApiResourceFacade;
use W2w\Lib\Apie\Exceptions\ResourceNotFoundException;
use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;
use W2w\Lib\Apie\Plugins\Core\DataLayers\NullDataLayer;
use W2w\Lib\Apie\Plugins\FileStorage\DataLayers\FileStorageDataLayer;
use W2w\Lib\Apie\Plugins\StatusCheck\ApiResources\Status;

class ApiResourceServiceProviderOverrideAnnotationTest extends AbstractLaravelTestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = $app->make('config');
        // Setup default database to use sqlite :memory:
        $config->set('database.default', 'testbench');
        $config->set(
            'database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            ]
        );
        $config->set(
            'apie',
            [
                'resources' => [ApplicationInfo::Class, Status::class, DomainObjectForFileStorage::class],
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
                ],
                'resource-config' => [
                    ApplicationInfo::class => [
                        'persistClass' => FileStorageDataLayer::class,
                        'retrieveClass' => FileStorageDataLayer::class
                    ],
                    Status::class => [
                        'persistClass' => NullDataLayer::class,
                        'retrieveClass' => StatusCheckRetriever::class
                    ],
                ]
            ]
        );
    }

    public function testApiResourceFacade()
    {
        /** @var ApiResourceFacade $class */
        $class = $this->app->get(ApiResourceFacade::class);
        $this->assertInstanceOf(ApiResourceFacade::class, $class);
        $this->expectException(ResourceNotFoundException::class);
        $class->get(ApplicationInfo::class, 'name', null);
    }
}
