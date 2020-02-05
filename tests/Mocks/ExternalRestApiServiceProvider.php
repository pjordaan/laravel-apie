<?php

namespace W2w\Laravel\Apie\Tests\Mocks;

use W2w\Laravel\Apie\Providers\AbstractRestApiServiceProvider;
use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;
use W2w\Lib\Apie\Plugins\StatusCheck\ApiResources\Status;

class ExternalRestApiServiceProvider extends AbstractRestApiServiceProvider
{
    protected function getApiName(): string
    {
        return 'test';
    }

    protected function getApiConfig(): array
    {
        return [
            'resources' => [ApplicationInfo::Class, Status::class, DomainObjectForFileStorage::class],
            'api-url' => 'external-api/test',
            'metadata'               => [
                'title'            => 'Laravel REST api',
                'version'          => '1.0',
                'hash'             => '12345',
                'description'      => 'OpenApi description',
                'terms-of-service' => '',
                'license'          => 'Apache 2.0',
                'license-url'      => 'https://www.apache.org/licenses/LICENSE-2.0.html',
                'contact-name'     => 'contact name',
                'contact-url'      => 'example.com',
                'contact-email'    => 'admin@example.com',
            ],
        ];
    }
}
