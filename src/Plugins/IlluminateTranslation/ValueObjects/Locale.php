<?php

namespace W2w\Laravel\Apie\Plugins\IlluminateTranslation\ValueObjects;

use W2w\Lib\Apie\Interfaces\ValueObjectInterface;
use W2w\Lib\Apie\Plugins\ValueObject\ValueObjects\StringEnumTrait;

class Locale implements ValueObjectInterface
{
    use StringEnumTrait;

    /**
     * @var string[]
     */
    public static $locales = [];

    final public static function getValidValues()
    {
        return self::$locales;
    }
}
