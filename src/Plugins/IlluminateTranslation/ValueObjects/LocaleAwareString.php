<?php


namespace W2w\Laravel\Apie\Plugins\IlluminateTranslation\ValueObjects;

use erasys\OpenApi\Spec\v3\Schema;
use JsonSerializable;
use W2w\Lib\Apie\Interfaces\ValueObjectInterface;
use W2w\Lib\Apie\OpenApiSchema\Factories\SchemaFactory;
use W2w\Lib\ApieObjectAccessNormalizer\Utils;

final class LocaleAwareString implements ValueObjectInterface, JsonSerializable
{
    private $locales = [];

    public function jsonSerialize()
    {
        return $this->locales;
    }

    public function merge(LocaleAwareString $other): LocaleAwareString
    {
        $result = clone $this;
        foreach ($other->locales as $key => $value) {
            $result->locales[$key] = $value;
        }
        return $result;
    }

    public function with(Locale $locale, string $value): LocaleAwareString
    {
        $result = clone $this;
        $result->locales[$locale->toNative()] = $value;
        return $result;
    }

    public function get(Locale  $locale): ?string
    {
        return $this->locales[$locale->toNative()] ?? null;
    }

    public static function fromNative($value)
    {
        $result = new LocaleAwareString();
        if (is_array($value) || is_iterable($value)) {
            foreach ($value as $key => $arrayValue) {
                $result->locales[$key] = Utils::toString($arrayValue);
            }
            return $result;
        }
        $result->locales[reset(Locale::$locales)] = Utils::toString($value);
        return $result;
    }

    public function toNative()
    {
        return $this->locales;
    }

    public static function toSchema(): Schema
    {
        return SchemaFactory::createStringSchema();
    }
}
