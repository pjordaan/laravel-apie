<?php
namespace W2w\Laravel\Apie\Events;

use erasys\OpenApi\Spec\v3\Document;

/**
 * Event triggered that an OpenAPI document was created. This can be used to modify the OpenApi spec being generated.
 */
class OpenApiSpecGenerated
{
    private $openApiDoc;

    /**
     * @param Document $openApiDoc
     */
    public function __construct(Document $openApiDoc)
    {
        $this->openApiDoc = $openApiDoc;
    }

    /**
     * @return Document
     */
    public function getDocument(): Document
    {
        return $this->openApiDoc;
    }

    /**
     * Replace the document. It's advisable to stop event propagation by returning false afterwards in your
     * listener.
     *
     * @param Document $document
     * @return OpenApiSpecGenerated
     */
    public function overrideDocument(Document $document): self {
        $this->openApiDoc = $document;
        return $this;
    }
}
