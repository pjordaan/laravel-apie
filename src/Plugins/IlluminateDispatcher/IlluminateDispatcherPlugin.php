<?php


namespace W2w\Laravel\Apie\Plugins\IlluminateDispatcher;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Illuminate\Validation\Factory;
use ReflectionClass;
use RewindableGenerator;
use W2w\Laravel\Apie\Contracts\HasApieRulesContract;
use W2w\Laravel\Apie\Plugins\IlluminateDispatcher\Helpers\PaginatorHelper;
use W2w\Lib\Apie\Events\DecodeEvent;
use W2w\Lib\Apie\Events\DeleteResourceEvent;
use W2w\Lib\Apie\Events\ModifySingleResourceEvent;
use W2w\Lib\Apie\Events\NormalizeEvent;
use W2w\Lib\Apie\Events\ResponseEvent;
use W2w\Lib\Apie\Events\RetrievePaginatedResourcesEvent;
use W2w\Lib\Apie\Events\RetrieveSingleResourceEvent;
use W2w\Lib\Apie\Events\StoreExistingResourceEvent;
use W2w\Lib\Apie\Events\StoreNewResourceEvent;
use W2w\Lib\Apie\PluginInterfaces\ResourceLifeCycleInterface;

/**
 * Link the Apie resource life cycle methods Laravel:
 * - event dispatcher
 * - validation rules with adding 'rules' in context.
 * - if the api resource is linked to a policy, check the policy.
 */
class IlluminateDispatcherPlugin implements ResourceLifeCycleInterface
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var Factory
     */
    private $validator;

    /**
     * @var AuthManager
     */
    private $gate;

    /**
     * @param Dispatcher $dispatcher
     * @param Factory $validator
     * @param Gate $gate
     */
    public function __construct(Dispatcher $dispatcher, Factory $validator, Gate $gate)
    {
        $this->dispatcher = $dispatcher;
        $this->validator = $validator;
        $this->gate = $gate;
    }

    /**
     * @param string $methodName
     * @param object $event
     */
    private function dispatch(string $methodName, object $event)
    {
        $eventName = 'apie.' . Str::snake(substr($methodName, 2));
        $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPreDeleteResource(DeleteResourceEvent $event)
    {
        if ($this->gate->getPolicyFor($event->getResourceClass())) {
            $this->gate->authorize('remove', [$event->getResourceClass(), $event->getId()]);
        }
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPostDeleteResource(DeleteResourceEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPreRetrieveResource(RetrieveSingleResourceEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPostRetrieveResource(RetrieveSingleResourceEvent $event)
    {
        if ($this->gate->getPolicyFor($event->getResource())) {
            $this->gate->authorize('view', $event->getResource());
        }

        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPreRetrieveAllResources(RetrievePaginatedResourcesEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPostRetrieveAllResources(RetrievePaginatedResourcesEvent $event)
    {
        $resources = $event->getResources();

        $event->setResources(
            PaginatorHelper::convertToPaginator(
                $resources,
                function (object $resource) {
                    return !$this->gate->getPolicyFor($resource) || $this->gate->allows('view', $resource);
                }
            )
        );
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPrePersistExistingResource(StoreExistingResourceEvent $event)
    {
        if ($this->gate->getPolicyFor($event->getResource())) {
            $this->gate->authorize('update', $event->getResource());
        }
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPostPersistExistingResource(StoreExistingResourceEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPreModifyResource(ModifySingleResourceEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPostModifyResource(ModifySingleResourceEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPreCreateResource(StoreNewResourceEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPostCreateResource(StoreNewResourceEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPrePersistNewResource(StoreExistingResourceEvent $event)
    {
        if ($this->gate->getPolicyFor($event->getResource())) {
            $this->gate->authorize('create', $event->getResource());
        }
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPostPersistNewResource(StoreExistingResourceEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPreCreateResponse(ResponseEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPostCreateResponse(ResponseEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPreCreateNormalizedData(NormalizeEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPostCreateNormalizedData(NormalizeEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPreDecodeRequestBody(DecodeEvent $event)
    {
        $this->dispatch(__FUNCTION__, $event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPostDecodeRequestBody(DecodeEvent $event)
    {
        $refl = new ReflectionClass($event->getResourceClass());
        if ($refl->implementsInterface(HasApieRulesContract::class)) {
            // maybe move this to a listener?
            $rules = $refl->getMethod('getApieRules')->invoke(null);
            $decodedData = json_decode(json_encode($event->getDecodedData()), true);
            $validation = $this->validator->make($decodedData, $rules);
            $validation->validate();
        }
        $this->dispatch(__FUNCTION__, $event);
    }
}
