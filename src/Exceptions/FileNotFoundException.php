<?php


namespace W2w\Laravel\Apie\Exceptions;

use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ApieException;

/**
 * Class thrown when the SQL file could not found. Is used by DatabaseQueryRetriever.
 */
class FileNotFoundException extends ApieException
{
    public function __construct(string $filename)
    {
        parent::__construct(500, 'File ' . $filename . ' not found!');
    }
}
