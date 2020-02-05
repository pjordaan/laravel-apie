<?php


namespace W2w\Laravel\Apie\Exceptions;


use W2w\Lib\Apie\Exceptions\ApieException;

class ApieContextMissingException extends ApieException
{
    public function __construct(string $context)
    {
        parent::__construct(
            500,
            'Context ' . $context . ' is not defined'
        );
    }
}
