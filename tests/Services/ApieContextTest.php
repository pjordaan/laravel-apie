<?php


namespace W2w\Laravel\Apie\Tests\Services;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use W2w\Laravel\Apie\Exceptions\ApieContextMissingException;
use W2w\Laravel\Apie\Services\ApieContext;
use W2w\Lib\Apie\DefaultApie;
use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;
use W2w\Lib\Apie\Plugins\StatusCheck\ApiResources\Status;

class ApieContextTest extends TestCase
{
    public function testGetApie()
    {
        $config = require __DIR__ . '/../../config/apie.php';
        $apie = DefaultApie::createDefaultApie(true);
        $container = new Container();
        $testItem = new ApieContext($container, $apie, $config, $config['contexts']);
        $this->assertSame($apie, $testItem->getApie());
    }

    public function testGetContext_missing()
    {
        $config = require __DIR__ . '/../../config/apie.php';
        $apie = DefaultApie::createDefaultApie(true);
        $container = new Container();
        $testItem = new ApieContext($container, $apie, $config, $config['contexts']);
        $this->expectException(ApieContextMissingException::class);
        $testItem->getContext('missing');
    }

    public function testGetContext()
    {
        $config = require __DIR__ . '/../../config/apie.php';
        $config['plugins'] = [];
        $config['resources'] = [];
        $config['contexts'] = [
            'v1' => $config,
            'v2' => $config,
        ];
        $config['contexts']['v1']['resources'] = [ApplicationInfo::class];
        $config['contexts']['v2']['resources'] = [Status::class];
        $apie = DefaultApie::createDefaultApie(true, [], null, false);
        $this->assertEquals([], $apie->getResources());
        $container = new Container();
        $testItem = new ApieContext($container, $apie, $config, $config['contexts']);
        $actualV1 = $testItem->getContext('v1');
        $this->assertSame($actualV1, $testItem->getContext('v1'), 'I expect to get the same instance');
        $this->assertEquals([ApplicationInfo::class], $actualV1->getApie()->getResources());
        $actualV2 = $testItem->getContext('v2');
        $this->assertEquals([Status::class], $actualV2->getApie()->getResources());
        $this->assertEquals(['v1' => $actualV1, 'v2' => $actualV2], $testItem->allContexts());
    }
}
