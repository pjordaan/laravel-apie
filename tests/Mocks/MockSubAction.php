<?php


namespace W2w\Laravel\Apie\Tests\Mocks;

use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;

class MockSubAction
{
    public function handle(ApplicationInfo $applicationInfo, int $additionalArgument): ReturnObjectForMockSubAction
    {
        return new ReturnObjectForMockSubAction($applicationInfo, $additionalArgument);
    }
}
