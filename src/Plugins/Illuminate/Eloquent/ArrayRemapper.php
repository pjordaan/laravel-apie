<?php

namespace W2w\Laravel\Apie\Plugins\Illuminate\Eloquent;

use Generator;
use UnexpectedValueException;

class ArrayRemapper
{
    public static function remap(array $mapping, array $input): array
    {
        $result = [];
        foreach (self::visit($mapping) as $key => $value) {
            $keys = explode('.', ltrim($key, '.'));
            $values = explode('.', $value);
            self::set($input, $result, $keys, $values);
        }
        return $result;
    }

    public static function reverseRemap(array $mapping, array $input): array
    {
        $result = [];
        foreach (self::visit($mapping) as $key => $value) {
            $keys = explode('.', $value);
            $values = explode('.', ltrim($key, '.'));
            self::set($input, $result, $keys, $values);
        }
        return $result;
    }

    private static function set(array& $input, array& $result, array $keys, array $values) {
        if (empty($values)) {
            throw new UnexpectedValueException('Mapping to empty key is not possible!');
        }
        $ptr = &$input;
        while ($keys) {
            $key = array_shift($keys);
            if (!array_key_exists($key, $ptr)) {
                break;
            }
            $ptr = &$ptr[$key];
        }
        $newValue = $ptr;
        $ptr = &$result;
        $prev = null;
        $lastKey = null;
        while (!empty($values)) {
            $key = array_shift($values);
            if (!array_key_exists($key, $ptr)) {
                $ptr[$key] = [];
            }
            $prev = &$ptr;
            $lastKey = $key;
            $ptr = &$ptr[$key];
        }
        $prev[$lastKey] = $newValue;
    }

    private static function visit(array $mapping, string $prefix = ''): Generator
    {
        foreach ($mapping as $key => $value) {
            if (is_array($value)) {
                yield from self::visit($value, $prefix . '.' . $key);
                continue;
            }
            yield $prefix . '.' . $key => $value;
        }
    }
}
