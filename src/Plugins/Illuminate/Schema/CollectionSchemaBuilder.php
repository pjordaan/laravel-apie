<?php

namespace W2w\Laravel\Apie\Plugins\Illuminate\Schema;

use erasys\OpenApi\Spec\v3\Schema;
use W2w\Lib\Apie\OpenApiSchema\OpenApiSchemaGenerator;
use W2w\Lib\Apie\PluginInterfaces\DynamicSchemaInterface;

/**
 * Schema for Illuminate collections.
 */
class CollectionSchemaBuilder implements DynamicSchemaInterface
{
    public function __invoke(
        string $resourceClass,
        string $operation,
        array $groups,
        int $recursion,
        OpenApiSchemaGenerator $generator
    ): ?Schema {
        return new Schema([
            'type' => 'array',
            'items' => new Schema([]),
        ]);
    }
}
