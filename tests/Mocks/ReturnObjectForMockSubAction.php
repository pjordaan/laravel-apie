<?php


namespace W2w\Laravel\Apie\Tests\Mocks;


use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;

class ReturnObjectForMockSubAction
{
    /**
     * @var ApplicationInfo
     */
    private $applicationInfo;
    /**
     * @var int
     */
    private $additionalArgument;

    public function __construct(ApplicationInfo $applicationInfo, int $additionalArgument)
    {
        $this->applicationInfo = $applicationInfo;
        $this->additionalArgument = $additionalArgument;
    }

    /**
     * @return int
     */
    public function getAdditionalArgument(): int
    {
        return $this->additionalArgument;
    }

    public function getAppName(): string
    {
        return $this->applicationInfo->getAppName();
    }
}
