<?php


namespace W2w\Laravel\Apie\Tests\Plugins\IlluminateTranslation\ValueObjects;

use PHPUnit\Framework\TestCase;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\ValueObjects\Locale;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\ValueObjects\LocaleAwareString;

class LocaleAwareStringTest extends TestCase
{
    public function testFromNative()
    {
        Locale::$locales = ['en', 'nl', 'be'];
        $testItem = LocaleAwareString::fromNative('This is a test');
        $this->assertEquals(
            ['en' => 'This is a test'],
            $testItem->toNative()
        );
        $this->assertEquals('This is a test', $testItem->get(new Locale('en')));
        $this->assertEquals(null, $testItem->get(new Locale('nl')));
        $actual = $testItem->with(new Locale('nl'), 'Dit is een test');
        $this->assertEquals(null, $testItem->get(new Locale('nl')));
        $this->assertEquals('Dit is een test', $actual->get(new Locale('nl')));
        $this->assertEquals(
            [
                'en' => 'This is a test',
                'nl' => 'Dit is een test',
            ],
            $actual->toNative()
        );
    }

    public function testToSchema()
    {
        $actual = LocaleAwareString::toSchema();
        $this->assertEquals('string', $actual->type);
    }
}
