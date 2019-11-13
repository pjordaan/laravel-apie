<?php


namespace W2w\Laravel\Apie\Tests\Services\Mock;

use W2w\Lib\Apie\ValueObjects\StringEnumTrait;
use W2w\Lib\Apie\ValueObjects\ValueObjectInterface;

class EnumValueObject implements ValueObjectInterface
{
    use StringEnumTrait;

    const A = 'a';

    const B = 'b';
}
