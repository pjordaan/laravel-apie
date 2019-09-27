<?php
use W2w\Lib\Apie\ApiResources\App;
use W2w\Lib\Apie\ApiResources\Status;
use W2w\Lib\Apie\Retrievers\AppRetriever;
use W2w\Lib\Apie\Retrievers\StatusCheckRetriever;

return [
    /**
     * A list of classes to be used as Api resources.
     */
    'resources'              => [App::class, Status::class],
    /**
     * If true, all persisting and retrieving will be mocked, so you will effectively get a mock api server.
     */
    'mock'                   => env('APIE_MOCK_SERVER', false),
    /**
     * If mock is true, some retrievers can be skipped and will keep working.
     */
    'mock-skipped-resources' => [AppRetriever::class, StatusCheckRetriever::class],
    /**
     * In case your application is not in the root of the website you require to configure the base url to get the correct
     * paths in the open api spec.
     */
    'base-url'               => '',
    /**
     * Route prefix of your api calls.
     */
    'api-url'                => '/api',
    /**
     * Disable loading the routes by the service provider.
     */
    'disable-routes'         => false,
    /**
     * Enable openapi test page with swagger ui if set. If falsy value this route is not being loaded.
     */
    'swagger-ui-test-page'      => '/swagger-ui',
    /**
     * Specific metadata used in the output of the open api specs.
     */
    'metadata'               => [
        'title'            => env('APIE_OPENAPI_TITLE', 'Laravel REST api'),
        'version'          => env('APIE_OPENAPI_VERSION', '1.0'),
        'hash'             => env('APIE_OPENAPI_HASH') ?? trim(`git rev-parse HEAD`),
        'description'      => env('APIE_OPENAPI_DESCRIPTION', ''),
        'terms-of-service' => env('APIE_OPENAPI_TERMS_OF_SERVICE_URL', ''),
        'license'          => env('APIE_OPENAPI_LICENSE', 'Apache 2.0'),
        'license-url'      => env('APIE_OPENAPI_LICENSE_URL', 'https://www.apache.org/licenses/LICENSE-2.0.html'),
        'contact-name'     => env('APIE_OPENAPI_CONTACT_NAME', 'Way2web Software'),
        'contact-url'      => env('APIE_OPENAPI_CONTACT_URL', ''),
        'contact-email'    => env('APIE_OPENAPI_CONTACT_EMAIL', ''),
    ],
];
