<?php

namespace W2w\Laravel\Apie\Tests\Plugins\Illuminate\Normalizers;

use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;
use W2w\Laravel\Apie\Plugins\Illuminate\Normalizers\LazyCollectionNormalizer;
use W2w\Laravel\Apie\Tests\Services\Mock\EnumValueObject;
use W2w\Lib\Apie\Plugins\ValueObject\Normalizers\ValueObjectNormalizer;
use W2w\Lib\ApieObjectAccessNormalizer\Normalizers\ApieObjectAccessNormalizer;

class LazyCollectionNormalizerTest extends TestCase
{
    /**
     * @var Serializer
     */
    private $serializer;

    protected function setUp(): void
    {
        if (!class_exists(LazyCollection::class)) {
            $this->markTestSkipped('Only works in Laravel 6+');
        }
        $this->serializer = new Serializer(
            [
                new LazyCollectionNormalizer(),
                new ArrayDenormalizer(),
                new ValueObjectNormalizer(),
                new ApieObjectAccessNormalizer(),
            ],
            []
        );
    }

    public function testNormalize()
    {
        $collection = LazyCollection::make(function () {
            yield from [1, 2, 3];
            yield LazyCollection::make(function () {
                yield from [4, 5];
            });
        });
        $actual = $this->serializer->normalize($collection);
        $this->assertEquals(
            [
                1,
                2,
                3,
                [4, 5],
            ],
            $actual
        );
    }

    public function testDenormalize_no_typehint()
    {
        $actual = $this->serializer->denormalize(
            [1, 2, 3],
            LazyCollection::class,
            null,
            []
        );
        $this->assertInstanceOf(LazyCollection::class, $actual);
        $this->assertEquals([1, 2, 3], $actual->all());
    }

    public function testDenormalize_with_typehint()
    {
        $actual = $this->serializer->denormalize(
            ['A', 'B', 'A'],
            LazyCollection::class,
            null,
            [
                'collection_resource' => EnumValueObject::class
            ]
        );
        $this->assertInstanceOf(LazyCollection::class, $actual);
        $this->assertEquals(
            [
                new EnumValueObject('A'),
                new EnumValueObject('B'),
                new EnumValueObject('A'),
            ],
            $actual->all()
        );
    }
}
