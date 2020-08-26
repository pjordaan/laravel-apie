<?php
namespace W2w\Laravel\Apie\Tests\Providers;

use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;
use W2w\Laravel\Apie\Tests\Mocks\ExternalRestApiServiceProvider;

class ApiResourceServiceProviderExternalServiceProviderTest extends AbstractLaravelTestCase
{
    public function test_external_rest_api_works_as_intended()
    {
        $this->withoutExceptionHandling();
        $url = route('apie.test.all', ['resource' => 'application_info'], false);
        $this->assertEquals('/external-api/test/application_info', $url);

        $response = $this->get($url, ['accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(
            [
                [
                    'app_name'    => 'Laravel',
                    'environment' => 'testing',
                    'hash'        => '12345',
                    'debug'       => false
                ],
            ]
        );
    }

    protected function getPackageProviders($app)
    {
        $res = parent::getPackageProviders($app);
        $res[] = ExternalRestApiServiceProvider::class;
        return $res;
    }
}
