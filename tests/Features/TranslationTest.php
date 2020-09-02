<?php


namespace W2w\Laravel\Apie\Tests\Features;

use Illuminate\Translation\FileLoader;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\ApiResources\Translation;
use W2w\Laravel\Apie\Providers\ApiResourceServiceProvider;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;
use W2w\Laravel\Apie\Tests\Mocks\TranslationServiceProvider;
use W2w\Lib\Apie\Core\SearchFilters\PhpPrimitive;
use W2w\Lib\Apie\Exceptions\InvalidIdException;
use W2w\Lib\Apie\Exceptions\InvalidPageLimitException;
use W2w\Lib\Apie\Exceptions\InvalidValueForValueObjectException;
use W2w\Lib\Apie\Exceptions\MethodNotAllowedException;
use W2w\Lib\Apie\Exceptions\PageIndexShouldNotBeNegativeException;
use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;
use W2w\Lib\ApieObjectAccessNormalizer\Errors\ErrorBag;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\CouldNotConvertException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\LocalizationableException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\NameNotFoundException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ObjectAccessException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ObjectWriteException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ValidationException;
use W2w\Lib\ApieObjectAccessNormalizer\Getters\ReflectionMethodGetter;
use W2w\Lib\ApieObjectAccessNormalizer\Setters\ReflectionMethodSetter;

class TranslationTest extends AbstractLaravelTestCase
{
    protected function getEnvironmentSetUp($application)
    {
        $config = $application->make('config');
        $config->set('app.name', __CLASS__);
        $resources = [ApplicationInfo::class, Translation::class];
        $config->set('apie.translations', ['en', 'nl', 'be']);
        $config->set('apie.resources', $resources);
    }

    public function testWrongLanguageThrows406()
    {
        $response = $this->get('/api/application_info', ['accept-language' => 'cn', 'accept' => 'application/json']);
        $response->assertJson([
            'message' => 'Accept language cn not accepted',
        ]);
        $response->assertStatus(406);
    }

    public function testAcceptLanguageIsAddedToSpec()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/api/doc.yml');
        $response->assertOk();
        $testFile =  __DIR__ . '/data/openapi-translation.yml';
        // file_put_contents($testFile, $response->baseResponse->getContent());
        $this->assertEquals(file_get_contents($testFile), $response->baseResponse->getContent());
    }

    public function testRetrieveWorksAsIntended()
    {
        $this->withoutExceptionHandling();
        $loader = app('translation.loader');
        $loader->addNamespace('unittest', __DIR__ . '/data');
        $response = $this->get('/api/translation/unittest::auth.failed', ['accept-language' => 'nl', 'accept' => 'application/json']);
        $response->assertOk();
        $response->assertHeader('Content-Language', 'nl');
        $response->assertJson([
            'id' => 'unittest::auth.failed',
            'translation' => 'Authorisatie gefaald',
            'locale' => 'nl',
        ]);
    }

    /**
     * @dataProvider subactionProvider
     */
    public function testSubActionWorksAsIntended(string $expected, string $id, string $language, array $placeholders, int $amount)
    {
        $this->withoutExceptionHandling();
        $loader = app('translation.loader');
        $loader->addNamespace('unittest', __DIR__ . '/data');
        $response = $this->postJson(
            '/api/translation/' . $id . '/withPlaceholders',
            [
                'replace' => $placeholders,
                'amount' => $amount,
            ],
            [
                'accept-language' => $language,
                'accept' => 'application/json',
                'content-type' => 'application/json'
            ]
        );
        $response->assertOk();
        $this->assertEquals(json_encode($expected), $response->getContent());
    }

    public function subactionProvider()
    {
        yield [
            'Er zijn meerdere appels',
            'unittest::plural.apples',
            'nl',
            [],
            12
        ];
        yield [
            'There are many apples',
            'unittest::plural.apples',
            'en',
            [],
            0
        ];
        yield [
            'Er zijn enkele peren',
            'unittest::plural.pears',
            'nl',
            [],
            12
        ];
        yield [
            'There are some pears',
            'unittest::plural.pears',
            'en',
            [],
            12
        ];
        yield [
            'Dit heeft een :placeholder placeholder',
            'unittest::plural.translation',
            'nl',
            [],
            1
        ];
        yield [
            'Dit heeft een pizza placeholder',
            'unittest::plural.translation',
            'nl',
            ['placeholder' => 'pizza'],
            1
        ];
        yield [
            'This has a pizza placeholder',
            'unittest::plural.translation',
            'en',
            ['placeholder' => 'pizza'],
            1
        ];
    }

    protected function getPackageProviders($app)
    {
        return [ApiResourceServiceProvider::class];
    }

    /**
     * @dataProvider localizationErrorProvider
     */
    public function testLocalizationableNormalizerWorks(string $expectedMessage, LocalizationableException $exception)
    {
        /** @var Serializer $serializer */
        $serializer = resolve(SerializerInterface::class);
        $this->assertEquals($expectedMessage, $serializer->normalize($exception)['message']);
    }

    public function localizationErrorProvider()
    {
        yield [
            'Value "42" for value object php_primitive is not in the right format',
            new InvalidValueForValueObjectException('42', PhpPrimitive::class),
        ];
        yield [
            'Id "this is not an id" is not valid as identifier',
            new InvalidIdException('this is not an id'),
        ];
        yield [
            'page should not be lower than 0',
            new PageIndexShouldNotBeNegativeException(),
        ];
        yield [
            'Method PIZZA is not allowed',
            new MethodNotAllowedException('PIZZA'),
        ];
        yield [
            'limit should not be lower than 1',
            new InvalidPageLimitException()
        ];
        yield [
            'A validation error occurred.',
            new ValidationException(['error' => ['name' => 'test']]),
        ];
        yield [
            'Bond007 not found!',
            new NameNotFoundException('Bond007'),
        ];
        yield [
            'Expected int, got string',
            new CouldNotConvertException('int', 'string')
        ];
        yield [
            'Could not read property localizationErrorProvider: "test"',
            new ObjectAccessException(new ReflectionMethodGetter(new ReflectionMethod(__METHOD__)), 'fieldName', new RuntimeException('test')),
        ];
        yield [
            'Could not write property localizationErrorProvider: "test"',
            new ObjectWriteException(new ReflectionMethodSetter(new ReflectionMethod(__METHOD__)), 'fieldName', new RuntimeException('test')),
        ];
    }

    /**
     * @dataProvider validationProvider
     */
    public function testValidationLocalizationWorks(array $expected, ValidationException $exception)
    {
        /** @var Serializer $serializer */
        $serializer = resolve(SerializerInterface::class);
        $this->assertEquals($expected, $serializer->normalize($exception));
    }

    public function validationProvider()
    {
        yield [
            [
                'type' => "ValidationException",
                'message' => 'A validation error occurred.',
                'code' => 0,
                'errors' => [],
            ],
            new ValidationException([])
        ];
        $bag = new ErrorBag('');
        $bag->addThrowable('test', new ObjectWriteException(new ReflectionMethodSetter(new ReflectionMethod(__METHOD__)), 'fieldName', new RuntimeException('test')));
        $exception = new ValidationException($bag);
        yield [
            [
                'type' => "ValidationException",
                'message' => 'A validation error occurred.',
                'code' => 0,
                'errors' => [
                    'test' => ['Could not write property validationProvider: "test"']
                ],
            ],
            $exception
        ];
        $anotherBag = new ErrorBag('');
        $anotherBag->addThrowable('field', $exception);
        yield [
            [
                'type' => "ValidationException",
                'message' => 'A validation error occurred.',
                'code' => 0,
                'errors' => [
                    'test' => ['Could not write property validationProvider: "test"']
                ],
            ],
            new ValidationException($anotherBag)
        ];
    }
}
