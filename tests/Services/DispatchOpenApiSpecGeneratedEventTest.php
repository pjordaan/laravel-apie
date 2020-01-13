<?php


namespace W2w\Laravel\Apie\Tests\Services;

use erasys\OpenApi\Spec\v3\Document;
use erasys\OpenApi\Spec\v3\Info;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;
use W2w\Laravel\Apie\Tests\Services\Mock\MockEventServiceProvider;
use W2w\Lib\Apie\ApiResources\ApplicationInfo;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSpecGenerator;

class DispatchOpenApiSpecGeneratedEventTest extends AbstractLaravelTestCase
{
    protected function getEnvironmentSetUp($application)
    {
        $this->setUpDatabase($application);
        $config = $application->make('config');
        $config->set(
            'apie',
            [
                'resources' => [ApplicationInfo::class],
                'metadata'  => [
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

    protected function getPackageProviders($app)
    {
        $res = parent::getPackageProviders($app);
        $res[] = MockEventServiceProvider::class;
        return $res;
    }

    public function testOverrideSpecWorksAsIntended()
    {
        MockEventServiceProvider::$override = new Document(
            new Info('Pizza Override API', 'final'),
            []
        );
        $this->assertEquals(
            MockEventServiceProvider::$override,
            resolve(OpenApiSpecGenerator::class)->getOpenApiSpec()
        );
    }
}
