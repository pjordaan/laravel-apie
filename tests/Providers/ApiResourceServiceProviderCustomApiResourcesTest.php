<?php
namespace W2w\Laravel\Apie\Tests\Providers;

use Illuminate\Support\ServiceProvider;
use W2w\Laravel\Apie\Models\Status;
use W2w\Laravel\Apie\Providers\ApiResourceServiceProvider;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;
use W2w\Lib\Apie\Core\ApiResourceFacade;
use W2w\Lib\Apie\Core\Resources\ApiResourcesInterface;
use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;

class ApiResourceServiceProviderCustomApiResourcesTest extends AbstractLaravelTestCase
{
    protected function getPackageProviders($app)
    {
        $dummy = new class($app) extends ServiceProvider
        {
            public function register()
            {
                $this->app->singleton(
                    'service-name',
                    function () {
                        return new class implements ApiResourcesInterface
                        {
                            public function getApiResources(): array
                            {
                                return [ApplicationInfo::class];
                            }
                        };
                    }
                );
            }
        };

        return [get_class($dummy), ApiResourceServiceProvider::class];
    }

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
                'resources'         => [ApplicationInfo::class, Status::class],
                'resources-service' => 'service-name',
                'metadata'          => [
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
        $appResponse = $class->get(ApplicationInfo::class, 'name', null);
        /** @var App $resource */
        $resource = $appResponse->getResource();
        $expected = new ApplicationInfo(
            'Laravel',
            'testing',
            '12345',
            false
        );
        $this->assertEquals($expected, $resource);

        $apiResources = $this->app->get(ApiResourcesInterface::class);

        $this->assertEquals([ApplicationInfo::class], $apiResources->getApiResources());
    }
}
