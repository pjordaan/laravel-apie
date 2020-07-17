<?php

namespace W2w\Laravel\Apie\Plugins\IlluminateTranslation\ApiResources;

use W2w\Lib\Apie\Annotations\ApiResource;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\DataLayers\TranslationRetriever;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\ValueObjects\Locale;

/**
 * @ApiResource(
 *    retrieveClass=TranslationRetriever::class
 * )
 */
class Translation
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $translation;

    /**
     * @var Locale
     */
    private $locale;

    public function __construct(string $id, string $translation, Locale $locale)
    {
        $this->id = $id;
        $this->translation = $translation;
        $this->locale = $locale;
    }

    /**
     * Get the id.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the translation.
     *
     * @return string
     */
    public function getTranslation(): string
    {
        return $this->translation;
    }

    /**
     * Get the locale.
     *
     * @return Locale
     */
    public function getLocale(): Locale
    {
        return $this->locale;
    }
}
