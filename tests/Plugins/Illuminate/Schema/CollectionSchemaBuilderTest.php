<?php


namespace W2w\Laravel\Apie\Tests\Plugins\Illuminate\Schema;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use W2w\Laravel\Apie\Plugins\Illuminate\Schema\CollectionSchemaBuilder;
use W2w\Lib\Apie\OpenApiSchema\SchemaGenerator;

class CollectionSchemaBuilderTest extends TestCase
{
    public function testCorrect()
    {
        $item = new CollectionSchemaBuilder();
        $mock = $this->prophesize(SchemaGenerator::class)->reveal();
        $actual = $item->__invoke(Collection::class, 'get', [], 1, $mock);
        $this->assertEquals('array', $actual->type);
        $this->assertNotNull($actual->items);
    }
}
