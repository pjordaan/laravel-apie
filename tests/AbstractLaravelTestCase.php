<?php
namespace W2w\Laravel\Apie\Tests;

use Orchestra\Testbench\TestCase;
use W2w\Laravel\Apie\Providers\ApiResourceServiceProvider;

abstract class AbstractLaravelTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [ApiResourceServiceProvider::class];
    }
}
