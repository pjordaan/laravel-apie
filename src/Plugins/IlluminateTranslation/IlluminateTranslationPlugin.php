<?php

namespace W2w\Laravel\Apie\Plugins\IlluminateTranslation;

use erasys\OpenApi\Spec\v3\Document;
use erasys\OpenApi\Spec\v3\Operation;
use erasys\OpenApi\Spec\v3\Parameter;
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
use W2w\Lib\Apie\PluginInterfaces\OpenApiEventProviderInterface;
use W2w\Lib\Apie\PluginInterfaces\ResourceLifeCycleInterface;

class IlluminateTranslationPlugin implements OpenApiEventProviderInterface, ResourceLifeCycleInterface
{
    /**
     * @var array
     */
    private $locales;

    /**
     * @param string[] $locales
     */
    public function __construct(array $locales)
    {
        $this->locales = $locales;
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
    }

    public function onPreCreateNormalizedData(NormalizeEvent $event)
    {
    }

    public function onPostCreateNormalizedData(NormalizeEvent $event)
    {
    }
}
