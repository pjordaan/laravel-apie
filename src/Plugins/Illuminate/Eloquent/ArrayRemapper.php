<?php

namespace W2w\Laravel\Apie\Plugins\Illuminate\Eloquent;

use Generator;

class ArrayRemapper
{
    public static function remap(array $mapping, array $input): array
    {
        $staticMapping = [];
        $dynamicMapping = [];
        foreach (self::flattenArray($mapping) as $key => $value) {
            if (strpos($key, '*') !== false) {
                $dynamicMapping[self::toRegex($key)] = self::toReplacement($value);
            } else {
                $staticMapping[$key] = $value;
            }
        }
        $result = [];

        foreach (self::visit($input, $staticMapping, $dynamicMapping) as $key => $value) {
            $keys = explode('.', $key);
            self::set($result, $keys, $value);
        }
        return $result;
    }

    public static function reverseRemap(array $mapping, array $input): array
    {
        $staticMapping = [];
        $dynamicMapping = [];
        foreach (self::flattenArray($mapping) as $value => $key) {
            if (strpos($key, '*') !== false) {
                $dynamicMapping[self::toRegex($key)] = self::toReplacement($value);
            } else {
                $staticMapping[$key] = $value;
            }
        }
        $result = [];

        foreach (self::visit($input, $staticMapping, $dynamicMapping) as $key => $value) {
            $keys = explode('.', $key);
            self::set($result, $keys, $value);
        }
        return $result;
    }

    private static function set(array& $input, array $keys, $value) {
        $ptr = &$input;
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!array_key_exists($key, $ptr)) {
                $ptr[$key] = [];
            }
            $ptr = &$ptr[$key];
        }
        $key = array_shift($keys);
        $ptr[$key] = $value;
    }

    private static function flattenArray(array $input, string $prefixKey = ''): Generator
    {
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                yield from self::flattenArray($value, $prefixKey . '.' . $key);
                continue;
            }
            yield ltrim($prefixKey . '.' . $key, '.') => $value;
        }
    }

    private static function visit(array& $input, array $staticMapping, array $dynamicMapping): Generator
    {

        $input = iterator_to_array(self::flattenArray($input));


        foreach ($input as $key => $value) {
            if (isset($staticMapping[$key])) {
                yield $staticMapping[$key] => $value;
                continue;
            }
            foreach ($dynamicMapping as $regex => $result) {
                if (preg_match($regex, $key)) {
                    $calculatedKey = preg_replace($regex, $result, $key);
                    yield $calculatedKey => $value;
                    break;
                }
            }
        }
    }

    private static function toRegex(string $input): string
    {
        return '/^' . str_replace('\\*', '([a-zA-Z0-9_-]+)', preg_quote($input, '/')) . '$/';
    }

    private static function toReplacement(string $input): string
    {
        $counter = 1;
        return preg_replace_callback(
                '/' . preg_quote('*', '/') . '/',
                function () use (&$counter) {
                    $res = '${' . $counter . '}';
                    $counter++;
                    return $res;
                },
                $input
            );
    }
}
