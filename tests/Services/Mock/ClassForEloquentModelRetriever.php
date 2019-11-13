<?php


namespace W2w\Laravel\Apie\Tests\Services\Mock;

use Ramsey\Uuid\Uuid;
use W2w\Laravel\Apie\Services\Retrievers\EloquentModelRetriever;
use W2w\Laravel\Apie\Tests\Mocks\ModelForEloquentModelRetriever;
use W2w\Lib\Apie\Annotations\ApiResource;

/**
 * @ApiResource(
 *     retrieveClass=EloquentModelRetriever::class,
 *     persistClass=EloquentModelRetriever::class,
 *     context={
 *         "model": ModelForEloquentModelRetriever::class
 *     }
 * )
 */
class ClassForEloquentModelRetriever
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
