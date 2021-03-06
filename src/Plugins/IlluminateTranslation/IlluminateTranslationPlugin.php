<?php

namespace W2w\Laravel\Apie\Plugins\IlluminateTranslation;

use erasys\OpenApi\Spec\v3\Document;
use erasys\OpenApi\Spec\v3\Operation;
use erasys\OpenApi\Spec\v3\Parameter;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Application;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\Normalizers\LocationableExceptionNormalizer;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\Normalizers\ValidationExceptionNormalizer;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\SubActions\TransChoiceSubAction;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\ValueObjects\Locale;
use W2w\Lib\Apie\Events\DecodeEvent;
use W2w\Lib\Apie\Events\DeleteResourceEvent;
use W2w\Lib\Apie\Events\ModifySingleResourceEvent;
use W2w\Lib\Apie\Events\NormalizeEvent;
use W2w\Lib\Apie\Events\ResponseEvent;
use W2w\Lib\Apie\Events\RetrievePaginatedResourcesEvent;
use W2w\Lib\Apie\Events\RetrieveSingleResourceEvent;
use W2w\Lib\Apie\Events\StoreExistingResourceEvent;
use W2w\Lib\Apie\Events\StoreNewResourceEvent;
use W2w\Lib\Apie\PluginInterfaces\ApieAwareInterface;
use W2w\Lib\Apie\PluginInterfaces\ApieAwareTrait;
use W2w\Lib\Apie\PluginInterfaces\NormalizerProviderInterface;
use W2w\Lib\Apie\PluginInterfaces\OpenApiEventProviderInterface;
use W2w\Lib\Apie\PluginInterfaces\ResourceLifeCycleInterface;
use W2w\Lib\Apie\PluginInterfaces\SubActionsProviderInterface;
use W2w\Lib\Apie\Plugins\Core\Normalizers\ExceptionNormalizer;

class IlluminateTranslationPlugin implements ApieAwareInterface, OpenApiEventProviderInterface, ResourceLifeCycleInterface, SubActionsProviderInterface, NormalizerProviderInterface
{
    use ApieAwareTrait;

    /**
     * @var array
     */
    private $locales;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Application
     */
    private $application;

    /**
     * @param string[] $locales
     * @param Translator $translator
     */
    public function __construct(array $locales, Translator $translator, Application $application)
    {
        $this->locales = $locales;
        $this->translator = $translator;
        $this->application = $application;
        Locale::$locales = $this->locales;
    }

    public function onOpenApiDocGenerated(Document $document): Document
    {
        foreach ($document->paths as $path) {
            $this->patchOperation($path->get);
            $this->patchOperation($path->put);
            $this->patchOperation($path->post);
            $this->patchOperation($path->patch);
            $this->patchOperation($path->delete);
            $this->patchOperation($path->options);
            $this->patchOperation($path->head);
            $this->patchOperation($path->trace);
        }
        return $document;
    }

    private function patchOperation(?Operation $operation)
    {
        if ($operation === null) {
            return;
        }
        if (null === $operation->parameters) {
            $operation->parameters = [];
        }
        Locale::$locales = $this->locales;
        $operation->parameters[] = new Parameter(
            'Accept-Language',
            Parameter::IN_HEADER,
            'language',
            [
                'schema' => Locale::toSchema(),
            ]
        );
    }

    public function onPreDeleteResource(DeleteResourceEvent $event)
    {
    }

    public function onPostDeleteResource(DeleteResourceEvent $event)
    {
    }

    public function onPreRetrieveResource(RetrieveSingleResourceEvent $event)
    {
    }

    public function onPostRetrieveResource(RetrieveSingleResourceEvent $event)
    {
    }

    public function onPreRetrieveAllResources(RetrievePaginatedResourcesEvent $event)
    {
    }

    public function onPostRetrieveAllResources(RetrievePaginatedResourcesEvent $event)
    {
    }

    public function onPrePersistExistingResource(StoreExistingResourceEvent $event)
    {
    }

    public function onPostPersistExistingResource(StoreExistingResourceEvent $event)
    {
    }

    public function onPreDecodeRequestBody(DecodeEvent $event)
    {
    }

    public function onPostDecodeRequestBody(DecodeEvent $event)
    {
    }

    public function onPreModifyResource(ModifySingleResourceEvent $event)
    {
    }

    public function onPostModifyResource(ModifySingleResourceEvent $event)
    {
    }

    public function onPreCreateResource(StoreNewResourceEvent $event)
    {
    }

    public function onPostCreateResource(StoreNewResourceEvent $event)
    {
    }

    public function onPrePersistNewResource(StoreExistingResourceEvent $event)
    {
    }

    public function onPostPersistNewResource(StoreExistingResourceEvent $event)
    {
    }

    public function onPreCreateResponse(ResponseEvent $event)
    {
    }

    public function onPostCreateResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $locale = $this->application->getLocale();
        if ($locale && !$response->hasHeader('Content-Language')) {
            $event->setResponse(
                $response
                    ->withAddedHeader('Content-Language', $this->application->getLocale())
                    ->withAddedHeader('Vary', 'Accept-Language')
            );
        }

    }

    public function onPreCreateNormalizedData(NormalizeEvent $event)
    {
    }

    public function onPostCreateNormalizedData(NormalizeEvent $event)
    {
    }

    public function getSubActions()
    {
        return [
            'withPlaceholders' => [new TransChoiceSubAction($this->translator)]
        ];
    }

    public function getNormalizers(): array
    {
        $normalizer = new LocationableExceptionNormalizer(
            new ExceptionNormalizer($this->getApie()->isDebug()),
            $this->application->make(Translator::class)
        );
        return [
            new ValidationExceptionNormalizer($normalizer, $this->application->make(Translator::class)),
            $normalizer,
        ];
    }
}
