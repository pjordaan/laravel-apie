<?php
namespace W2w\Laravel\Apie\Tests\Controllers;

use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;

class SwaggerUiControllerTest extends AbstractLaravelTestCase
{
    public function test_works_without_config()
    {
        $response = $this->get('/swagger-ui');
        $this->assertStringContainsString('http://localhost/api/doc.yml', (string) $response->getContent());
    }
}
