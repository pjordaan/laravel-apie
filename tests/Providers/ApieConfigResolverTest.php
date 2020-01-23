<?php
namespace W2w\Laravel\Apie\Tests\Providers;

use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use W2w\Laravel\Apie\Providers\ApieConfigResolver;
use W2w\Lib\Apie\Annotations\ApiResource;
use W2w\Lib\Apie\ApiResources\ApplicationInfo;
use W2w\Lib\Apie\Persisters\NullPersister;
use W2w\Lib\Apie\Retrievers\ApplicationInfoRetriever;

class ApieConfigResolverTest extends TestCase
{
    /**
     * @dataProvider resolveConfigProvider
     */
    public function testResolveConfig(array $expected, array $input)
    {
        $this->assertEquals($expected, ApieConfigResolver::resolveConfig($input));
    }

    public function resolveConfigProvider()
    {
        $expected = require __DIR__ . '/../../config/apie.php';
        ApieConfigResolver::addExceptionsForExceptionMapping($expected['exception-mapping']);

        yield [$expected, []];

        $actual = $expected;
        yield [$expected, $actual];

        $actual['metadata']['terms-of-service'] = 'this-url-will-get-a-https-prefix.nl/test?query=true';
        $expected['metadata']['terms-of-service'] = 'https://this-url-will-get-a-https-prefix.nl/test?query=true';

        $expected['resource-config'] = $actual['resource-config'] = [
            ApplicationInfo::class => ApiResource::createFromArray(
                [
                    'persistClass' => NullPersister::class,
                    'retrieveClass' => ApplicationInfoRetriever::class
                ]
            )
        ];
        yield [$expected, $actual];
        $actual['resource-config'] = [
            ApplicationInfo::class => [
                'persistClass' => NullPersister::class,
                'retrieveClass' => ApplicationInfoRetriever::class
            ]
        ];
        yield [$expected, $actual];
    }

    /**
     * @dataProvider invalidConfigProvider
     */
    public function testResolveConfig_invalid_config(string $expectedExceptionClass, array $input)
    {
        $this->expectException($expectedExceptionClass);
        ApieConfigResolver::resolveConfig($input);
    }

    public function invalidConfigProvider()
    {
        $defaults = require __DIR__ . '/../../config/apie.php';
        $test1 = $defaults;
        $test1['metadata']['terms-of-service'] = 'øøøøøøø.-412..™™™.---.nl';
        yield [InvalidOptionsException::class, $test1];
        $test2 = $defaults;
        $test2['resources'] = 12;
        yield [InvalidOptionsException::class, $test2];
        $test3 = $defaults;
        $test3['option-that-does-not-exist'] = true;
        yield [UndefinedOptionsException::class, $test3];
        $test4 = $defaults;
        $test4['resource-config'] = new ApiResource();
        yield [InvalidOptionsException::class, $test4];
        $test5 = $defaults;
        $test5['resource-config'] = ['pizza'];
        yield [InvalidOptionsException::class, $test5];
    }
}
