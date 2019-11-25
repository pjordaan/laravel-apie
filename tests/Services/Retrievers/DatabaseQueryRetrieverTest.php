<?php


namespace W2w\Laravel\Apie\Tests\Services\Retrievers;

use W2w\Laravel\Apie\Exceptions\ApiResourceContextException;
use W2w\Laravel\Apie\Exceptions\FileNotFoundException;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;
use W2w\Laravel\Apie\Tests\Services\Mock\ClassForDatabaseQueryRetriever;
use W2w\Laravel\Apie\Tests\Services\Mock\MissingConfigClassForDatabaseQueryRetriever;
use W2w\Laravel\Apie\Tests\Services\Mock\MissingFileClassForDatabaseQueryRetriever;
use W2w\Lib\Apie\ApiResourceFacade;
use W2w\Lib\Apie\Exceptions\ResourceNotFoundException;

class DatabaseQueryRetrieverTest extends AbstractLaravelTestCase
{
    protected function getEnvironmentSetUp($application)
    {
        $this->setUpDatabase($application);
        $config = $application->make('config');
        $config->set(
            'apie',
            [
                'resources' => [
                    ClassForDatabaseQueryRetriever::class,
                    MissingConfigClassForDatabaseQueryRetriever::class,
                    MissingFileClassForDatabaseQueryRetriever::class,
                ],
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

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/dbretrievermigration');
    }

    public function testRetrieve()
    {
        /** @var ApiResourceFacade $facade */
        $facade = $this->app->get(ApiResourceFacade::class);
        $actual = $facade->get(ClassForDatabaseQueryRetriever::class, 'a', null)->getResource();
        $this->assertEquals(new ClassForDatabaseQueryRetriever('a', 2101566889, 100), $actual);
    }

    public function testRetrieve_not_found()
    {
        /** @var ApiResourceFacade $facade */
        $facade = $this->app->get(ApiResourceFacade::class);
        $this->expectException(ResourceNotFoundException::class);
        $facade->get(ClassForDatabaseQueryRetriever::class, 'c', null)->getResource();
    }

    public function testRetrieve_missing_configuration()
    {
        /** @var ApiResourceFacade $facade */
        $facade = $this->app->get(ApiResourceFacade::class);
        $this->expectException(ApiResourceContextException::class);
        $facade->get(MissingConfigClassForDatabaseQueryRetriever::class, 'a', null);
    }

    public function testRetrieve_missing_file()
    {
        /** @var ApiResourceFacade $facade */
        $facade = $this->app->get(ApiResourceFacade::class);
        $this->expectException(FileNotFoundException::class);
        $facade->get(MissingFileClassForDatabaseQueryRetriever::class, 'a', null);
    }

    public function testRetrieveAll()
    {
        /** @var ApiResourceFacade $facade */
        $facade = $this->app->get(ApiResourceFacade::class);
        $actual = $facade->getAll(ClassForDatabaseQueryRetriever::class, null)->getResource();
        $expected = [
            new ClassForDatabaseQueryRetriever('a', 2101566889, 100),
            new ClassForDatabaseQueryRetriever('b', 2137389542, 100)
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testRetrieveAll_missing_configuration()
    {
        /** @var ApiResourceFacade $facade */
        $facade = $this->app->get(ApiResourceFacade::class);
        $this->expectException(ApiResourceContextException::class);
        $facade->getAll(MissingConfigClassForDatabaseQueryRetriever::class, null);
    }
}
