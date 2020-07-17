<?php


namespace W2w\Laravel\Apie\Tests\Features;

use Illuminate\Translation\FileLoader;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\ApiResources\Translation;
use W2w\Laravel\Apie\Providers\ApiResourceServiceProvider;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;
use W2w\Laravel\Apie\Tests\Mocks\TranslationServiceProvider;
use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;

class TranslationTest extends AbstractLaravelTestCase
{
    protected function getEnvironmentSetUp($application)
    {
        $config = $application->make('config');
        $config->set('app.name', __CLASS__);
        $resources = [ApplicationInfo::class, Translation::class];
        $config->set('apie.translations', ['en', 'nl', 'be']);
        $config->set('apie.resources', $resources);
    }

    public function testWrongLanguageThrows406()
    {
        $response = $this->get('/api/application_info', ['accept-language' => 'cn', 'accept' => 'application/json']);
        $response->assertJson([
            'message' => 'Accept language cn not accepted',
        ]);
        $response->assertStatus(406);
    }

    public function testAcceptLanguageIsAdded()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/api/doc.yml');
        $response->assertOk();
        $testFile =  __DIR__ . '/data/openapi-translation.yml';
        // file_put_contents($testFile, $response->baseResponse->getContent());
        $this->assertEquals(file_get_contents($testFile), $response->baseResponse->getContent());
    }

    public function testRetrieveWorksAsIntended()
    {
        $this->withoutExceptionHandling();
        $loader = app('translation.loader');
        $loader->addNamespace('unittest', __DIR__ . '/data');
        $response = $this->get('/api/translation/unittest::auth.failed', ['accept-language' => 'nl', 'accept' => 'application/json']);
        $response->assertOk();
        $response->assertJson([
            'id' => 'unittest::auth.failed',
            'translation' => 'Authorisatie gefaald',
            'locale' => 'nl',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [ApiResourceServiceProvider::class];
    }
}
