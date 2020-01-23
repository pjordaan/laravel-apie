<?php

namespace W2w\Laravel\Apie\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Serializer;
use W2w\Lib\Apie\Encodings\FormatRetriever;
use W2w\Lib\Apie\Exceptions\ValidationException as ApieValidationException;
use W2w\Lib\Apie\Models\ApiResourceFacadeResponse;

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
            resolve(Serializer::class),
            [],
            $exception,
            resolve(FormatRetriever::class),
            $request->header('accept')
        );
        return $this->httpFoundationFactory->createResponse($apiRes->getResponse()->withStatus($statusCode));
    }

    public function isApieAction(Request $request): bool
    {
        $route = $request->route();
        if (!$route) {
            return false;
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
