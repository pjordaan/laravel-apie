# cache integration
Make sure you enable caching in the config:
```php
<?php
// config/apie.php
return [
    'caching' => true    
];
```
By default it on if environment variable APP_DEBUG is false.

## Laravel 6+
In Laravel 6+, PSR6 cache is enabled by default and requires no extra packages.
## Laravel 5.*
In Laravel 5.*, you require to add the package madewithlove/illuminate-psr-cache-bridge

```bash
composer require madewithlove/illuminate-psr-cache-bridge
```
That's all!

## Emptying cache
The cache is stored in storage/app/apie-cache. Right now it can not be configured.

## Custom cache implementation
If you want to implement your own caching mechanism, the best solution is to make an Apie plugin that implements W2w\Lib\Apie\PluginInterfaces\CacheItemPoolProviderInterface

```php
<?php
use Cache\Adapter\Redis\RedisCachePool;
use Psr\Cache\CacheItemPoolInterface;
use RedisArray;
use W2w\Lib\Apie\PluginInterfaces\CacheItemPoolProviderInterface;

class SomeCacheProviderInterface implements CacheItemPoolProviderInterface
{
    public function getCacheItemPool(): CacheItemPoolInterface
    {
        $client = new RedisArray(['127.0.0.1:6379', '127.0.0.2:6379']);
        return new RedisCachePool($client);
    }
}
```
And in the config:
```php
<?php
//config/apie.php
return [
    'plugins' => [SomeCacheProviderInterface::class],
];
```
