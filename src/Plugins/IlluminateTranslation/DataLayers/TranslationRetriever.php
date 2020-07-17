<?php


namespace W2w\Laravel\Apie\Plugins\IlluminateTranslation\DataLayers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\ApiResources\Translation;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\ValueObjects\Locale;
use W2w\Lib\Apie\Core\SearchFilters\SearchFilterRequest;
use W2w\Lib\Apie\Exceptions\BadConfigurationException;
use W2w\Lib\Apie\Exceptions\MethodNotAllowedException;
use W2w\Lib\Apie\Interfaces\ApiResourceRetrieverInterface;

class TranslationRetriever implements ApiResourceRetrieverInterface
{
    /**
     * @var Translator
     */
    private $translator;
    /**
     * @var Application
     */
    private $application;

    public function __construct(Translator $translator, Application $application)
    {
        $this->translator = $translator;
        $this->application = $application;
    }

    public function retrieve(string $resourceClass, $id, array $context)
    {
        if ($resourceClass !== Translation::class) {
            throw new BadConfigurationException(__CLASS__ . ' only works with Translation');
        }
        return new Translation($id, $this->translator->get($id, [], $this->application->getLocale()), new Locale($this->application->getLocale()));
    }

    public function retrieveAll(string $resourceClass, array $context, SearchFilterRequest $searchFilterRequest
    ): iterable {
        throw new MethodNotAllowedException('GET');
    }
}
