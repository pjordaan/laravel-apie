<?php


namespace W2w\Laravel\Apie\Middleware\Bridge;

use erasys\OpenApi\Spec\v3\Components;
use erasys\OpenApi\Spec\v3\MediaType;
use erasys\OpenApi\Spec\v3\Operation;
use erasys\OpenApi\Spec\v3\Reference;
use erasys\OpenApi\Spec\v3\Response;
use Exception;
use W2w\Laravel\Apie\Contracts\ApieMiddlewareBridgeContract;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSchemaGenerator;

class MiddlewareToStatusCodeMapper implements ApieMiddlewareBridgeContract
{
    /**
     * @var OpenApiSchemaGenerator
     */
    private $schemaGenerator;

    /**
     * @var array
     */
    private $mapping;

    public function __construct(OpenApiSchemaGenerator $schemaGenerator, array $mapping)
    {
        $this->schemaGenerator = $schemaGenerator;
        $this->mapping = $mapping;
    }

    public function patch(Operation $operation, Components $components, string $middlewareClass)
    {
        $schema = $this->schemaGenerator->createSchema(
            Exception::class,
            'get',
            ['base', 'read', 'get']
        );
        $media = new MediaType([
            'schema' => $schema
        ]);
        foreach ($this->mapping as $statusCode => $data) {
            list($className, $identifier, $description) = $data;
            $operation->responses[$statusCode] = new Response(
                $description,
                [
                    'schema' => new Reference('#/components/responses/' . $identifier)
                ]
            );
            if ($middlewareClass === $className || is_a($middlewareClass, $className, true)) {
                $components->responses[$identifier] = new Response($description, [clone $media]);
            }
        }
    }
}
