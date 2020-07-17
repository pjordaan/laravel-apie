<?php

namespace W2w\Laravel\Apie\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class HandleAcceptLanguage
{
    /**
     * @var Application
     */
    private $app;

    /**
     * HttpLocaleMiddleware constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string[] $languages
     * @return mixed
     */
    public function handle($request, Closure $next, string ...$languages)
    {
        array_unshift($languages, null);
        $locale = $request->getPreferredLanguage($languages);
        if ($locale === null) {
            throw new NotAcceptableHttpException('Accept language ' . $request->headers->get('Accept-Language') . ' not accepted');
        }
        $this->app->setLocale($locale);

        return $next($request);
    }
}
