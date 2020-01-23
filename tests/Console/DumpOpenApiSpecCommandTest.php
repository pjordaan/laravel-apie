<?php
namespace W2w\Laravel\Apie\Tests\Console;

use Illuminate\Foundation\Testing\PendingCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;

class DumpOpenApiSpecCommandTest extends AbstractLaravelTestCase
{
    private $filename;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filename = sys_get_temp_dir() . '/' . rand();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    public function test_no_argument_throws_exception()
    {
        $this->expectException(RuntimeException::class);
        $res = $this->artisan('apie:dump-open-api');
        if ($res instanceof PendingCommand) {
            $res->execute();
        }
    }

    public function test_happy_flow()
    {
        $actual = $this->artisan('apie:dump-open-api', ['filename' => $this->filename]);
        if ($actual instanceof PendingCommand) {
            $actual = $actual->execute();
        }
        $this->assertEquals(0, $actual);
        $this->assertTrue(file_exists($this->filename));
        $expectedFile = __DIR__ . '/data/expected-contents.json';
        //file_put_contents($expectedFile, file_get_contents($this->filename));
        $this->assertEquals(file_get_contents($this->filename), file_get_contents($expectedFile));
    }
}
