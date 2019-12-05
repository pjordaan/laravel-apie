<?php


namespace W2w\Laravel\Apie\Services;


use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use W2w\Lib\Apie\ApiResourceFacade;
use W2w\Lib\Apie\ClassResourceConverter;
use W2w\Lib\Apie\Models\ApiResourceFacadeResponse;

class RequestToFacadeResponseConverter
{
    private $facade;

    private $converter;

    public function __construct(ApiResourceFacade $facade, ClassResourceConverter $converter)
    {
        $this->facade    = $facade;
        $this->converter = $converter;
    }

    public function convertUnknownResourceClassToResponse(ServerRequestInterface $request): ApiResourceFacadeResponse
    {
        $attributes = $request->getAttributes();
        $resourceClass = null;
        if (isset($attributes['resourceClass'])) {
            $resourceClass = $attributes['resourceClass'];
        } else if (isset($attributes['resource_class'])) {
            $resourceClass = $attributes['resource_class'];
        } else if (isset($attributes['resource'])) {
            $resourceClass = $this->converter->denormalize($attributes['resource']);
        } else {
            throw new RuntimeException('I expect a resourceClass, resource_class or resource routing attribute');
        }
        return $this->convertRequestToResponse($resourceClass, $request);
    }

    public function convertResourceToResponse(string $resourceClass, $id, ServerRequestInterface $request): ApiResourceFacadeResponse
    {
        switch ($request->getMethod()) {
            case 'GET':
                return $this->facade->get($resourceClass, $id, $request);
            case 'POST':
                return $this->facade->post($resourceClass, $request);
            case 'PUT':
                return $this->facade->put($resourceClass, $id, $request);
            case 'DELETE':
                return $this->facade->delete($resourceClass, $id);
        }
        throw new MethodNotAllowedHttpException(['GET', 'POST', 'PUT', 'DELETE']);
    }

    public function convertRequestToResponse(string $resourceClass, ServerRequestInterface $request): ApiResourceFacadeResponse
    {
        $attributes = $request->getAttributes();
        $hasId = array_key_exists('id', $attributes);
        $id = (string) ($attributes['id'] ?? '');
        if ($hasId) {
            return $this->convertResourceToResponse($resourceClass, $id, $request);
        }

        switch ($request->getMethod()) {
            case 'GET':
                return $this->facade->getAll($resourceClass, $request);
            case 'POST':
                return $this->facade->post($resourceClass, $request);
        }
        throw new MethodNotAllowedHttpException(['GET', 'POST']);
    }
}
