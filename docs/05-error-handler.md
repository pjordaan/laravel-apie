# changing the error handler
In Laravel and Lumen we need to make sure error messages are provided correctly. If the default framework is checked
we need to change the error handler in src/App/Exceptions/Handler.php:

```php
<?php
//src/App/Exception/Exceptions/Handler.php

use W2w\Laravel\Apie\Services\ApieExceptionToResponse;

class Handler extends ExceptionHandler
{
    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
     public function render($request, Exception $exception)
     {
        /** @var ApieExceptionToResponse $apieHandler */
        $apieHandler = app(ApieExceptionToResponse::class);
        if ($apieHandler->isApieAction($request)) {
            return $apieHandler->convertExceptionToApieResponse($request, $exception);
        }
        // any logic you already had...
        return parent::render($request, $exception);
     }
}

```
