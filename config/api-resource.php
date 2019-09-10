<?php
use W2w\Lib\Apie\ApiResources\App;
use W2w\Lib\Apie\ApiResources\Status;

return [
    'enable-serializer'      => true,
    'resources'              => [App::class, Status::class],
    'mock'                   => env('APIE_MOCK_SERVER', false),
    'mock-skipped-resources' => [AppRetriever::class, StatusCheckRetriever::class],
    'api-url'                => '/api',
    'metadata'               => [
        'title'            => env('APIE_OPENAPI_TITLE', 'Laravel REST api'),
        'version'          => env('APIE_OPENAPI_VERSION', '1.0'),
        'description'      => env('APIE_OPENAPI_DESCRIPTION', ''),
        'terms-of-service' => env('APIE_OPENAPI_TERMS_OF_SERVICE_URL', ''),
        'license'          => env('APIE_OPENAPI_LICENSE', 'Apache 2.0'),
        'license-url'      => env('APIE_OPENAPI_LICENSE_URL', 'https://www.apache.org/licenses/LICENSE-2.0.html'),
        'contact-name'     => env('APIE_OPENAPI_CONTACT_NAME', 'Way2web Software'),
        'contact-url'      => env('APIE_OPENAPI_CONTACT_URL', ''),
        'contact-email'    => env('APIE_OPENAPI_CONTACT_EMAIL', ''),
    ],
];
