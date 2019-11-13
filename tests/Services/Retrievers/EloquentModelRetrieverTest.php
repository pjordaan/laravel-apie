<?php


namespace W2w\Laravel\Apie\Tests\Services\Retrievers;

use Illuminate\Auth\GenericUser;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;
use W2w\Laravel\Apie\Tests\Mocks\ModelForEloquentModelRetriever;
use W2w\Laravel\Apie\Tests\Mocks\PolicyServiceProvider;
use W2w\Laravel\Apie\Tests\Services\Mock\ClassForEloquentModelRetriever;
use W2w\Laravel\Apie\Tests\Services\Mock\EnumValueObject;
use W2w\Lib\Apie\ApiResourceFacade;
use W2w\Lib\Apie\Exceptions\ResourceNotFoundException;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;

class EloquentModelRetrieverTest extends AbstractLaravelTestCase
{
    protected function getEnvironmentSetUp($application)
    {
        $this->setUpDatabase($application);
        $config = $application->make('config');
        $config->set(
            'apie',
            [
                'resources' => [
                    ClassForEloquentModelRetriever::class,
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
        $this->be(new GenericUser([]));
    }

    public function testRetrieve()
    {
        /** @var ApiResourceFacade $facade */
        $facade = $this->app->get(ApiResourceFacade::class);
        $actual = $facade->get(ClassForEloquentModelRetriever::class, '42', null)->getResource();
        $this->assertEquals(new ClassForEloquentModelRetriever(1716179948, new EnumValueObject('a'), '42'), $actual);
    }

    public function testRetrieve_not_found()
    {
        /** @var ApiResourceFacade $facade */
        $facade = $this->app->get(ApiResourceFacade::class);
        $this->expectException(ResourceNotFoundException::class);
        $facade->get(ClassForEloquentModelRetriever::class, '666', null);
    }

    public function testRetrieveAll()
    {
        /** @var ApiResourceFacade $facade */
        $facade = $this->app->get(ApiResourceFacade::class);
        $actual = $facade->getAll(ClassForEloquentModelRetriever::class, 0, 2, null)->getResource();
        $expected = [
            new ClassForEloquentModelRetriever(1178568022, new EnumValueObject('a'), '0'),
            new ClassForEloquentModelRetriever(1273124119, new EnumValueObject('a'), '1'),
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testPersistNew()
    {
        /** @var ApiResourceFacade $facade */
        $facade = $this->app->get(ApiResourceFacade::class);
        $request = (new ServerRequest())->withBody(new Stream('data://text/plain,{"enum_column":"b","value":42}'));
        $this->assertFalse(ModelForEloquentModelRetriever::where('id', 201)->exists());
        $actual = $facade->post(ClassForEloquentModelRetriever::class, $request)->getResource();
        $expected = new ClassForEloquentModelRetriever(42, new EnumValueObject('b'), 200);
        $this->assertEquals($expected, $actual);
        $model = ModelForEloquentModelRetriever::find(200);
        $this->assertNotNull($model);

        if ($model) {
            $this->assertEquals(42, $model->value);
            $this->assertEquals('b', $model->enum_column);
            $this->assertEquals(200, $model->id);
        }
    }

    public function testPersistExisting()
    {
        /** @var ApiResourceFacade $facade */
        $facade = $this->app->get(ApiResourceFacade::class);
        $request = (new ServerRequest())->withBody(new Stream('data://text/plain,{"enum_column":"b","value":42}'));
        $actual = $facade->put(ClassForEloquentModelRetriever::class, 1, $request)->getResource();
        $expected = new ClassForEloquentModelRetriever(42, new EnumValueObject('a'), '1');
        $this->assertEquals($expected, $actual);

        $model = ModelForEloquentModelRetriever::find('1');
        $this->assertNotNull($model);

        if ($model) {
            $this->assertEquals(42, $model->value);
            $this->assertEquals('a', $model->enum_column);
            $this->assertEquals('1', $model->id);
        }

    }

    public function testRemove()
    {
        /** @var ApiResourceFacade $facade */
        $facade = $this->app->get(ApiResourceFacade::class);
        $this->assertTrue(ModelForEloquentModelRetriever::where('id', 1)->exists());

        $this->assertNull($facade->delete(ClassForEloquentModelRetriever::class, 1)->getResource());

        $this->assertFalse(ModelForEloquentModelRetriever::where('id', 1)->exists());
    }


    protected function getPackageProviders($app)
    {
        $res = parent::getPackageProviders($app);
        array_unshift($res, PolicyServiceProvider::class);
        return $res;
    }

}
