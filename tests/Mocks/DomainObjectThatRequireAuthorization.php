<?php


namespace W2w\Laravel\Apie\Tests\Mocks;

use W2w\Lib\Apie\Annotations\ApiResource;
use W2w\Lib\Apie\Plugins\Core\DataLayers\MemoryDataLayer;

/**
 * @ApiResource(
 *     persistClass=MemoryDataLayer::class,
 *     retrieveClass=MemoryDataLayer::class
 * )
 * @see MockPolicy
 */
class DomainObjectThatRequireAuthorization
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $one;

    /**
     * @var string
     */
    public $two;
}
