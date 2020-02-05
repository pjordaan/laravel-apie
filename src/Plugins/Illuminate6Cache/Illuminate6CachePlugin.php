<?php

namespace W2w\Laravel\Apie\Plugins\Illuminate6Cache;

use Illuminate\Container\Container;
use Psr\Cache\CacheItemPoolInterface;
use W2w\Lib\Apie\PluginInterfaces\CacheItemPoolProviderInterface;

class Illuminate6CachePlugin implements CacheItemPoolProviderInterface
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getCacheItemPool(): CacheItemPoolInterface
    {
        return $this->container->get('cache.psr6');
    }
}
