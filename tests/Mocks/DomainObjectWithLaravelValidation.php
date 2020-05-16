<?php


namespace W2w\Laravel\Apie\Tests\Mocks;

use W2w\Laravel\Apie\Contracts\HasApieRulesContract;
use W2w\Lib\Apie\Annotations\ApiResource;
use W2w\Lib\Apie\Plugins\Core\DataLayers\MemoryDataLayer;

/**
 * @ApiResource(
 *     persistClass=MemoryDataLayer::class,
 *     retrieveClass=MemoryDataLayer::class
 * )
 */
class DomainObjectWithLaravelValidation implements HasApieRulesContract
{
    public $one;

    public $two;


    /**
     * Returns a list of validation rules just like a laravel form request works.
     *
     * @return array
     */
    public static function getApieRules(): array
    {
        return [
            'one' => ['required', 'numeric'],
            'two' => ['required', 'numeric'],
        ];
    }
}
