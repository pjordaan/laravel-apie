<?php
namespace W2w\Laravel\Apie\Controllers;

use Illuminate\Contracts\Routing\UrlGenerator as LaravelUrlGenerator;
use Laravel\Lumen\Routing\UrlGenerator as LumenUrlGenerator;
use W2w\Laravel\Apie\Exceptions\FileNotFoundException;
use W2w\Laravel\Apie\Services\ApieContext;
use Zend\Diactoros\Response\TextResponse;

/**
 * Renders swagger UI for the openAPI spec generated by Apie.
 */
class SwaggerUiController
{
    private $apieContext;

    private $urlGenerator;

    private $htmlLocation;

    /**
     * @param ApieContext                           $apieContext
     * @param LaravelUrlGenerator|LumenUrlGenerator $urlGenerator
     * @param string                                $htmlLocation
     */
    public function __construct(ApieContext $apieContext, $urlGenerator, string $htmlLocation)
    {
        $this->apieContext = $apieContext;
        $this->urlGenerator = $urlGenerator;
        $this->htmlLocation = $htmlLocation;
    }

    public function __invoke()
    {
        $contents = @file_get_contents($this->htmlLocation);
        if (false === $contents) {
            throw new FileNotFoundException($this->htmlLocation);
        }
        $context = $this->apieContext->getActiveContext()->getContextKey();
        $contextString = empty($context) ? '' : (implode('.', $context) . '.');
        $url = $this->urlGenerator->route('apie.' . $contextString . 'docsyaml');

        return new TextResponse(
            str_replace('{{ url }}', $url, $contents),
            200,
            [
                'content-type' => 'text/html'
            ]
        );
    }
}
