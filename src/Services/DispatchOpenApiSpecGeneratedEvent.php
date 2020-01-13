<?php


namespace W2w\Laravel\Apie\Services;

use erasys\OpenApi\Spec\v3\Document;
use W2w\Laravel\Apie\Events\OpenApiSpecGenerated;

/**
 * Dispatch OpenApiSpecGenerated event where someone can listen to to make changes (or even override completely)
 * the generated specification.
 */
class DispatchOpenApiSpecGeneratedEvent
{
    public static function onApiGenerated(Document $document): Document
    {
        $event = new OpenApiSpecGenerated($document);
        event($event);
        return $event->getDocument();
    }
}
