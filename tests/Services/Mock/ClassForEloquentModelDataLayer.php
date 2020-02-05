<?php


namespace W2w\Laravel\Apie\Tests\Services\Mock;

use W2w\Laravel\Apie\Plugins\Illuminate\DataLayers\EloquentModelDataLayer;
use W2w\Laravel\Apie\Tests\Mocks\ModelForEloquentModelDataLayer;
use W2w\Lib\Apie\Annotations\ApiResource;

/**
 * @ApiResource(
 *     retrieveClass=EloquentModelDataLayer::class,
 *     persistClass=EloquentModelDataLayer::class,
 *     context={
 *         "model": ModelForEloquentModelDataLayer::class
 *     }
 * )
 */
class ClassForEloquentModelDataLayer
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var int
     */
    private $value;

    /**
     * @var EnumValueObject
     */
    private $enumColumn;

    public function __construct(int $value, EnumValueObject $enumColumn, ?int $id = null)
    {
        $this->setValue($value);
        $this->enumColumn = $enumColumn;
        $this->id = $id;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getEnumColumn(): EnumValueObject
    {
        return $this->enumColumn;
    }

    public function setValue(int $value): self
    {
        $this->value = max(0, $value);
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
