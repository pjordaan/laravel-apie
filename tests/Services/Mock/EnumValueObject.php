<?php

namespace W2w\Laravel\Apie\Tests\Services\Mock;

use W2w\Lib\Apie\Interfaces\ValueObjectInterface;
use W2w\Lib\Apie\Plugins\ValueObject\ValueObjects\StringEnumTrait;

class EnumValueObject implements ValueObjectInterface
{
    use StringEnumTrait;

    const A = 'a';

    const B = 'b';
}
