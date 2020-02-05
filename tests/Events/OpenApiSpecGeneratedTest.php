<?php


namespace W2w\Laravel\Apie\Tests\Events;

use erasys\OpenApi\Spec\v3\Document;
use erasys\OpenApi\Spec\v3\Info;
use PHPUnit\Framework\TestCase;
use W2w\Laravel\Apie\Events\OpenApiSpecGenerated;

class OpenApiSpecGeneratedTest extends TestCase
{
    public function testItWorks()
    {
        $document = new Document(
            new Info('pizza api', '1.0'),
            []
        );
        $document2 = new Document(
            new Info('pizza api', '2.0'),
            []
        );
        $testItem = new OpenApiSpecGenerated($document);
        $this->assertSame($document, $testItem->getDocument());
        $this->assertEquals($testItem, $testItem->overrideDocument($document2));
        $this->assertSame($document2, $testItem->getDocument());
    }
}
