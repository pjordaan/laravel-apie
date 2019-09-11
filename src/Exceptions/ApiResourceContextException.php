<?php
namespace W2w\Laravel\Apie\Exceptions;

use W2w\Lib\Apie\Exceptions\ApieException;

/**
 * Exception thrown if an ApiResource is missing data in the context.
 */
class ApiResourceContextException extends ApieException
{
    public function __construct(string $resourceClass, string $options) {
        parent::__construct(
            500,
            'Resource ' . $resourceClass . ' misses ' . $options .' option in the ApiResource annotation'
        );
    }
}
