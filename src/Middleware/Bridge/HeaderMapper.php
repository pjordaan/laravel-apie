<?php


namespace W2w\Laravel\Apie\Middleware\Bridge;

use erasys\OpenApi\Spec\v3\Components;
use erasys\OpenApi\Spec\v3\Header;
use erasys\OpenApi\Spec\v3\MediaType;
use erasys\OpenApi\Spec\v3\Operation;
use erasys\OpenApi\Spec\v3\Reference;
use erasys\OpenApi\Spec\v3\Response;
use Exception;
use W2w\Laravel\Apie\Contracts\ApieMiddlewareBridgeContract;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSchemaGenerator;

class HeaderMapper implements ApieMiddlewareBridgeContract
{
    /**
     * @var array
     */
    private $mapping;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function patch(Operation $operation, Components $components, string $middlewareClass)
    {
        foreach ($this->mapping as $className => $data) {
            if ($middlewareClass === $className || is_a($middlewareClass, $className, true)) {
                list($header, $schema, $description) = $data;
                if (isset($operation->responses[200])) {
                    $operation->responses[200]->headers[$header] = new Header($description, ['schema' => $scheman]);
                }
            }
        }
    }
}
