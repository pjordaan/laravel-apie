<?php


namespace W2w\Laravel\Apie\Tests\Facades;

use W2w\Laravel\Apie\Facades\Apie;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;
use W2w\Lib\Apie\ApiResources\App;

class ApieTest extends AbstractLaravelTestCase
{
    public function testFacadeWorksAsIntended()
    {
        $appResponse = Apie::get(App::class, 'name', null);
        /**
 * @var App $resource 
*/
        $resource = $appResponse->getResource();
        $hash = include __DIR__ . '/../../config/apie.php';
        $expected = new App(
            'Laravel',
            'testing',
            $hash['metadata']['hash'],
            false
        );
        $this->assertEquals($expected, $resource);
    }
}
