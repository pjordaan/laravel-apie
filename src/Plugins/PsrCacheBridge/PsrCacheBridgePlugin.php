<?php

namespace W2w\Laravel\Apie\Plugins\PsrCacheBridge;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository;
use Madewithlove\IlluminatePsrCacheBridge\Laravel\CacheItemPool;
use Psr\Cache\CacheItemPoolInterface;
use W2w\Lib\Apie\PluginInterfaces\CacheItemPoolProviderInterface;

class PsrCacheBridgePlugin implements CacheItemPoolProviderInterface
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getCacheItemPool(): CacheItemPoolInterface
    {
        $repository = $this->container->make(Repository::class);
        return new CacheItemPool($repository);
    }
}
