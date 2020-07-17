<?php

use W2w\Laravel\Apie\Plugins\IlluminateDispatcher\IlluminateDispatcherPlugin;
use W2w\Lib\Apie\Annotations\ApiResource;
use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;
use W2w\Lib\Apie\Plugins\ApplicationInfo\DataLayers\ApplicationInfoRetriever;
use W2w\Lib\Apie\Plugins\StatusCheck\ApiResources\Status;
use W2w\Lib\Apie\Plugins\StatusCheck\DataLayers\StatusCheckRetriever;

return [
    /**
     * For BC reasons the ApieObjectNormalizer is still turned on. In Version 4 this one will be replaced
     * and the serialization will change. Set to false to get the version 4 functionality.
     * It has effect on the serialization and the schema generation.
     */
    'use_deprecated_apie_object_normalizer' => true,
    /**
     * A list of classes to be used as Api resources.
     */
    'resources'              => [ApplicationInfo::class, Status::class],

    /**
     * If this config contains content, an accept header can be added and Apie will add Laravel middleware to set the correct locale.
     */
    'translations' => [/** 'nl', 'de' */],

    /**
     * Sub actions adds additional commands as a sub url route and will return the return value back as answer
     * If properly typehinted this can be auto-wired by Apie and the return value can be used as well.
     */
    'subactions'             => [
        // 'slug' => [Class1::class, Class2::class]
    ],

    /**
     * Object access only makes sense if use_deprecated_apie_object_normalizer is false. An Object Access instance
     * can tell how to access an object. The Object Class will be instantiated with the app container.
     */
    'object-access'             => [
        // ClassName::class => ObjectAccessClass::class,
    ],

    /**
     * If caching is enabled and available (psr6-illuminate bridge or laravel 6+) results are being cached.
     */
    'caching'                => env('APIE_CACHING', !env('APP_DEBUG', false)),

    /**
     * Load additional apie plugins.
     */
    'plugins'                => [IlluminateDispatcherPlugin::class],

    /**
     * Indicate the list of classes to be used as Api resources comes from a service in the service container instead.
     */
    'resources-service'      => null,
    /**
     * If true, all persisting and retrieving will be mocked, so you will effectively get a mock api server.
     */
    'mock'                   => env('APIE_MOCK_SERVER', false),
    /**
     * If mock is true, some retrievers can be skipped and will keep working.
     */
    'mock-skipped-resources' => [ApplicationInfoRetriever::class, StatusCheckRetriever::class],
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
     * Set route middleware for resource calls.
     */
    'apie-middleware'      => [],
    /**
     * Set route middleware for swagger ui test page.
     */
    'swagger-ui-test-page-middleware' => [],

    /**
     * If true any Laravel controller can use ApiResourceFacadeResponse as typehint to use the API parsing in their controller.
     * Has no effect in Lumen.
     */
    'bind-api-resource-facade-response' => true,
    /**
     * Specific metadata used in the output of the open api specs.
     */
    'metadata'               => [
        'title'            => env('APIE_OPENAPI_TITLE', 'Laravel REST api'),
        'version'          => env('APIE_OPENAPI_VERSION', '1.0'),
        'hash'             => env('APIE_OPENAPI_HASH', '-') /* recommended: trim(`git rev-parse HEAD`) to get the git SHA */,
        'description'      => env('APIE_OPENAPI_DESCRIPTION', ''),
        'terms-of-service' => env('APIE_OPENAPI_TERMS_OF_SERVICE_URL', ''),
        'license'          => env('APIE_OPENAPI_LICENSE', 'Apache 2.0'),
        'license-url'      => env('APIE_OPENAPI_LICENSE_URL', 'https://www.apache.org/licenses/LICENSE-2.0.html'),
        'contact-name'     => env('APIE_OPENAPI_CONTACT_NAME', 'Way2web Software'),
        'contact-url'      => env('APIE_OPENAPI_CONTACT_URL', ''),
        'contact-email'    => env('APIE_OPENAPI_CONTACT_EMAIL', ''),
    ],
    /**
     * This option is only useful if ApieExceptionToResponse is used in the error handler and is about mapping exception
     * classes to a status code. A few of them are predefined.
     */
    'exception-mapping' => [
    ],

    /**
     * Overrides/configure api resources which class to use. This can be used in case you do not want to use annotations to configure
     * api resources.
     * @see ApiResource
     */
    'resource-config' => [
       /*ApplicationInfo::class => ['retrieveClass' => StatusCheckRetriever::class],*/
    ],

    /**
     * Creates a child REST api inheriting all settings of the parent configuration. Technically it is possible to make
     * a child REST api inside a child REST api but we do not support any of this functionality yet.
     */
    'contexts' => [
        /*
        'v1' => ['resources' => ['ClassV1']],
        'v2' => ['resources' => ['ClassV2']],
         */
    ]
];
