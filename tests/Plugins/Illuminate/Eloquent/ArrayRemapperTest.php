<?php

namespace W2w\Laravel\Apie\Tests\Plugins\Illuminate\Eloquent;

use PHPUnit\Framework\TestCase;
use W2w\Laravel\Apie\Plugins\Illuminate\Eloquent\ArrayRemapper;

class ArrayRemapperTest extends TestCase
{
    /**
     * @dataProvider remapProvider
     */
    public function testRemap(array $expected, array $mapping, array $input)
    {
        $this->assertEquals($expected, ArrayRemapper::remap($mapping, $input));
    }

    public function remapProvider()
    {
        yield [
            [],
            [],
            []
        ];
        yield [
            [],
            [],
            ['a' => 1]
        ];
        yield [
            ['a' => 1],
            ['a' => 'a'],
            ['a' => 1]
        ];
        yield [
            ['b' => 1],
            ['a' => 'b'],
            ['a' => 1]
        ];
        yield [
            ['address' => ['a' => 1, 'c' => 13], 'b' => 12],
            ['a' => 'address.a', 'b' => 'b', 'c' => 'address.c'],
            ['a' => 1, 'b' => 12, 'c' => 13]
        ];
        yield [
            ['a' => 1, 'b' => 12, 'c' => 13],
            ['address.a' => 'a', 'b' => 'b', 'address.c' => 'c'],
            ['address' => ['a' => 1, 'c' => 13], 'b' => 12],
        ];
    }


    /**
     * @dataProvider reverseRemapProvider
     */
    public function testReverseRemap(array $expected, array $mapping, array $input)
    {
        $this->assertEquals($expected, ArrayRemapper::reverseRemap($mapping, $input));
    }

    public function reverseRemapProvider()
    {
        yield [
            [],
            [],
            []
        ];
        yield [
            [],
            [],
            ['a' => 1]
        ];
        yield [
            ['a' => 1],
            ['a' => 'a'],
            ['a' => 1]
        ];
        yield [
            ['a' => 1],
            ['a' => 'b'],
            ['b' => 1]
        ];
        yield [
            ['a' => 1, 'b' => 12, 'c' => 13],
            ['a' => 'address.a', 'b' => 'b', 'c' => 'address.c'],
            ['address' => ['a' => 1, 'c' => 13], 'b' => 12],
        ];
        yield [
            ['address' => ['a' => 1, 'c' => 13], 'b' => 12],
            ['address.a' => 'a', 'b' => 'b', 'address.c' => 'c'],
            ['a' => 1, 'b' => 12, 'c' => 13],
        ];
    }
}
