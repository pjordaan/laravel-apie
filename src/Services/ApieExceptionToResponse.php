<?php

namespace W2w\Laravel\Apie\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use W2w\Lib\Apie\Core\Models\ApiResourceFacadeResponse;
use W2w\Lib\Apie\Exceptions\ValidationException as ApieValidationException;
use W2w\Lib\Apie\Interfaces\ResourceSerializerInterface;

class ApieExceptionToResponse
{
    private $httpFoundationFactory;

    private $exceptionMapping;

    public function __construct(HttpFoundationFactory $httpFoundationFactory, array $exceptionMapping)
    {
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->exceptionMapping = $exceptionMapping;
    }

    public function convertExceptionToApieResponse(Request $request, Exception $exception): Response
    {
        $statusCode = 500;
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
        }
        $statusCode = $this->getStatusCodeFromClassMapping(get_class($exception)) ?? $statusCode;

        if ($exception instanceof ValidationException) {
            $statusCode = 422;
            $exception = new ApieValidationException($exception->errors());
        }
        $apiRes = new ApiResourceFacadeResponse(
            app(ResourceSerializerInterface::class),
            $exception,
            $request->header('accept')
        );
        try {
            $response = $apiRes->getResponse();
        } catch (NotAcceptableHttpException $notAcceptableHttpException) {
            // accept header is not allowed, so assume to return default accept header.
            $apiRes = new ApiResourceFacadeResponse(
                app(ResourceSerializerInterface::class),
                $exception,
                null
            );
            $response = $apiRes->getResponse();
        }
        return $this->httpFoundationFactory->createResponse($response->withStatus($statusCode));
    }

    public function isApieAction(Request $request): bool
    {
        $route = $request->route();
        if (!$route) {
            return false;
        }
        // needed for Lumen
        if (is_array($route)) {
            $name = $route['1']['as'] ?? '';
            return Str::startsWith($name, 'apie.');
        }
        return Str::startsWith($route->getName(), 'apie.');
    }

    private function getStatusCodeFromClassMapping(string $className): ?int
    {
        if (isset($this->exceptionMapping[$className])) {
            return (int) $this->exceptionMapping[$className];
        } else {
            foreach ($this->exceptionMapping as $mappedClassName => $intendedStatusCode) {
                if (is_a($className, $mappedClassName, true)) {
                    return (int) $intendedStatusCode;
                }
            }
        }
        return null;
    }
}
