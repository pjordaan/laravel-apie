<?php

namespace W2w\Laravel\Apie\Plugins\Illuminate\ObjectAccess;

use Illuminate\Contracts\Auth\Authenticatable;
use ReflectionClass;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\ObjectAccess;

/**
 * ObjectAccess for Authenticatable to remove security sensitive fields from mapping.
 *
 * @see Authenticatable
 */
class AuthObjectAccess extends ObjectAccess
{
    protected function getGetterMapping(ReflectionClass $reflectionClass): array
    {
        $parent = parent::getGetterMapping($reflectionClass);
        $result = [];
        foreach ($parent as $key => $keyMapping) {
            if (strpos($key, 'auth') === false && strpos($key, 'remember') === false) {
                $result[$key] = $keyMapping;
            }
        }
        return $result;
    }

    protected function getSetterMapping(ReflectionClass $reflectionClass): array
    {
        $parent = parent::getSetterMapping($reflectionClass);
        $result = [];
        foreach ($parent as $key => $keyMapping) {
            if (strpos($key, 'auth') === false && strpos($key, 'remember') === false) {
                $result[$key] = $keyMapping;
            }
        }
        return $result;
    }
}
