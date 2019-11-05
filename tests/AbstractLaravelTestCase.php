<?php
namespace W2w\Laravel\Apie\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;
use W2w\Laravel\Apie\Providers\ApiResourceServiceProvider;

abstract class AbstractLaravelTestCase extends TestCase
{
    /**
     * Set up database connection for test. Should be called from getEnvironmentSetup().
     *
     * @see TestCase::getEnvironmentSetUp()
     *
     * @param Application $application
     * @param string $db
     */
    protected function setUpDatabase(Application $application, string $db = ':memory:'): void
    {
        $config = $application['config'];
        $config->set('database.default', 'testbench');
        $config->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => $db,
            'prefix'   => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [ApiResourceServiceProvider::class];
    }
}
