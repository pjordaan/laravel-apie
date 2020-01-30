<?php
namespace W2w\Laravel\Apie\Tests;

use Illuminate\Support\ServiceProvider;
use W2w\Laravel\Apie\Providers\ApiResourceServiceProvider;
use W2w\Lib\Apie\ApiResources\ApplicationInfo;

class FeatureTest extends AbstractLaravelTestCase
{
    protected function getEnvironmentSetUp($application)
    {
        $config = $application->make('config');
        $config->set('app.name', __CLASS__);
    }

    protected function getPackageProviders($app)
    {
        $dummy = new class($app) extends ServiceProvider
        {
            public function boot()
            {
                require __DIR__ . '/MockControllers/routing.php';
            }

            public function register()
            {
            }
        };

        return [get_class($dummy), ApiResourceServiceProvider::class];
    }

    public function testApiResourceFacadeResponse_works_with_get_all()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/test-facade-response/application_info');
        $this->assertEquals('1 ' . ApplicationInfo::class . ' 104', $response->getContent());
    }

    public function testApiResourceFacadeResponse_works_with_id()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/test-facade-response/application_info/name');
        $this->assertEquals(ApplicationInfo::class . ' 102', $response->getContent());
    }

    public function testResource_typehint_works_with_id()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/test-resource-typehint/name');
        $this->assertEquals(__CLASS__, $response->getContent());
    }
}
