<?php

namespace W2w\Laravel\Apie\Tests\Plugins\Illuminate\Normalizers;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;
use W2w\Laravel\Apie\Plugins\Illuminate\Normalizers\CollectionNormalizer;
use W2w\Laravel\Apie\Tests\Services\Mock\EnumValueObject;
use W2w\Lib\Apie\Plugins\ValueObject\Normalizers\ValueObjectNormalizer;
use W2w\Lib\ApieObjectAccessNormalizer\Normalizers\ApieObjectAccessNormalizer;

class CollectionNormalizerTest extends TestCase
{
    /**
     * @var Serializer
     */
    private $serializer;

    protected function setUp(): void
    {
        $this->serializer = new Serializer(
            [
                new CollectionNormalizer(),
                new ArrayDenormalizer(),
                new ValueObjectNormalizer(),
                new ApieObjectAccessNormalizer(),
            ],
            []
        );
    }

    public function testNormalize()
    {
        $collection = new Collection([
            'pizza',
            'a',
            'a',
            'a',
            new Collection([])
        ]);
        $actual = $this->serializer->normalize($collection);
        $this->assertEquals(
            [
                'pizza',
                'a',
                'a',
                'a',
                [],
            ],
            $actual
        );
    }

    public function testDenormalize_no_typehint()
    {
        $actual = $this->serializer->denormalize(
            [1, 2, 3],
            Collection::class,
            null,
            []
        );
        $this->assertEquals(new Collection([1, 2, 3]), $actual);
    }

    public function testDenormalize_with_typehint()
    {
        $actual = $this->serializer->denormalize(
            ['A', 'B', 'A'],
            Collection::class,
            null,
            [
                'collection_resource' => EnumValueObject::class
            ]
        );
        $this->assertEquals(
            new Collection(
                [
                    new EnumValueObject('A'),
                    new EnumValueObject('B'),
                    new EnumValueObject('A'),
                ]
            ),
            $actual
        );
    }
}
