<?php


namespace W2w\Laravel\Apie\Tests\Services\Mock;

use erasys\OpenApi\Spec\v3\Document;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use W2w\Laravel\Apie\Events\OpenApiSpecGenerated;

class MockEventServiceProvider extends ServiceProvider
{
    /**
     * @var Document|null
     */
    public static $override;

    protected $listen = [
        OpenApiSpecGenerated::class => [__CLASS__ . '@overrideDoc'],
    ];

    /**
     * This typehint is needed for the dispatcher.
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    /**
     * @param OpenApiSpecGenerated $event
     */
    public function overrideDoc(OpenApiSpecGenerated $event)
    {
        if (self::$override) {
            $event->overrideDocument(self::$override);
        }
    }
}
