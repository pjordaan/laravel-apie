<?php


namespace W2w\Laravel\Apie\Contracts;

/**
 * If an Api resource implements this interface, Laravel validation will be added to the application.
 */
interface HasApieRulesContract
{
    /**
     * Returns a list of validation rules just like a laravel form request works.
     *
     * @return array
     */
    public static function getApieRules(): array;
}
