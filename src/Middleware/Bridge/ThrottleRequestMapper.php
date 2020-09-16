<?php


namespace W2w\Laravel\Apie\Middleware\Bridge;

use erasys\OpenApi\Spec\v3\Components;
use erasys\OpenApi\Spec\v3\Header;
use erasys\OpenApi\Spec\v3\MediaType;
use erasys\OpenApi\Spec\v3\Operation;
use erasys\OpenApi\Spec\v3\Reference;
use erasys\OpenApi\Spec\v3\Response;
use Exception;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use W2w\Laravel\Apie\Contracts\ApieMiddlewareBridgeContract;
use W2w\Lib\Apie\OpenApiSchema\Factories\SchemaFactory;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSchemaGenerator;

/**
 * Maps Apie spec with ThrottleRequest middleware.
 *
 * @see ThrottleRequests
 * @see ThrottleRequestsWithRedis
 */
class ThrottleRequestMapper implements ApieMiddlewareBridgeContract
{
    /**
     * @var OpenApiSchemaGenerator
     */
    private $schemaGenerator;

    public function __construct(OpenApiSchemaGenerator $schemaGenerator)
    {
        $this->schemaGenerator = $schemaGenerator;
    }

    public function patch(Operation $operation, Components $components, string $middlewareClass)
    {
        var_dump($middlewareClass);
        if ($middlewareClass !== ThrottleRequestsWithRedis::class || $middlewareClass !== ThrottleRequests::class) {
            return;
        }
        $schema = $this->schemaGenerator->createSchema(
            TooManyRequestsHttpException::class,
            'get',
            ['base', 'read', 'get']
        );
        $media = new MediaType([
            'schema' => $schema
        ]);
        $operation->responses[429] = new Response(
            'Request limit has been reached',
            [
                'schema' => new Reference('#/components/responses/TooManyRequests')
            ]
        );
        foreach ($operation->responses as $response) {
            $response->headers['X-RateLimit-Limit'] = new Header('Rate limit', ['schema' => SchemaFactory::createNumberSchema()]);
            $response->headers['X-RateLimit-Limit-Remaining'] = new Header('Requests remaining', ['schema' => SchemaFactory::createNumberSchema()]);
            $response->headers['Retry-After'] = new Header(null, ['schema' => SchemaFactory::createNumberSchema('timestamp')]);
            $response->headers['X-Ratelimit-Reset'] = new Header(null, ['schema' => SchemaFactory::createNumberSchema('timestamp')]);
        }
        $components->responses['TooManyRequests'] = new Response('Request limit has been reached', [clone $media]);
    }
}
