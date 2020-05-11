<?php
namespace W2w\Laravel\Apie\Tests;

use Illuminate\Support\ServiceProvider;
use W2w\Laravel\Apie\Providers\ApiResourceServiceProvider;
use W2w\Laravel\Apie\Tests\Mocks\MockSubAction;
use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ValidationException;

class FeatureTest extends AbstractLaravelTestCase
{
    protected function getEnvironmentSetUp($application)
    {
        $config = $application->make('config');
        $config->set('app.name', __CLASS__);
        $actions = ['sub' => [MockSubAction::class]];
        $config->set('apie.subactions', $actions);
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

    public function testResource_sub_action_works_with_arguments()
    {
        $this->withoutExceptionHandling();
        try {
            $response = $this->postJson(
                '/api/application_info/name/sub',
                ['additional_argument' => 42]
            );
            $response->assertJson(
                [
                    'app_name' => __CLASS__,
                    'additional_argument' => 42
                ]
            );
        } catch (ValidationException $validationException) {
            $this->fail('I did not expect a validation error with: ' . json_encode($validationException->getErrors()));
        }
    }
}
