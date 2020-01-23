<?php
namespace W2w\Laravel\Apie\Tests\Services;

use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use RuntimeException;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Serializer;
use W2w\Laravel\Apie\Services\ApieExceptionToResponse;
use W2w\Laravel\Apie\Tests\AbstractLaravelTestCase;
use W2w\Lib\Apie\Exceptions\ResourceNotFoundException;
use W2w\Lib\Apie\Exceptions\ValidationException;

class ApieExceptionToResponseTest extends AbstractLaravelTestCase
{
    /**
     * @dataProvider convertExceptionToApieResponseProvider
     */
    public function testConvertExceptionToApieResponse(
        int $statusCode,
        array $expectedResponse,
        string $expectedcontentType,
        Request $request,
        Exception $exception,
        string $format
    ) {
        /** @var ApieExceptionToResponse $testItem */
        $testItem = resolve(ApieExceptionToResponse::class);
        $actual = $testItem->convertExceptionToApieResponse($request, $exception);
        $this->assertEquals($expectedcontentType, $actual->headers->get('content-type'));
        $this->assertEquals($statusCode, $actual->getStatusCode());
        $decoded = resolve(Serializer::class)
            ->decode(
                $actual->getContent(),
                $format,
                [JsonDecode::ASSOCIATIVE => true]
            );
        $this->assertEquals($expectedResponse, $decoded);
    }

    public function convertExceptionToApieResponseProvider()
    {
        $xmlRequest = Request::create('/bla-die-blah');
        $jsonRequest = Request::create('/bla-die-blah');
        $jsonRequest->headers->add(['accept' => 'application/json']);

        yield [
            500,
            [
                'type' => 'RuntimeException',
                'message' => 'This is a test',
                'code' => 0,
            ],
            "application/xml",
            $xmlRequest,
            new RuntimeException('This is a test'),
            'xml',
        ];
        yield [
            500,
            [
                'type' => 'RuntimeException',
                'message' => 'This is a test',
                'code' => 0,
            ],
            "application/json",
            $jsonRequest,
            new RuntimeException('This is a test'),
            'json'
        ];
        yield [
            404,
            [
                'type' => 'ResourceNotFoundException',
                'message' => '"bla-die-blah" resource not found!',
                'code' => 0,
            ],
            "application/json",
            $jsonRequest,
            new ResourceNotFoundException('bla-die-blah'),
            'json'
        ];
        yield [
            415,
            [
                'type' => 'CircularReferenceException',
                'message' => 'circular reference found!',
                'code' => 0,
            ],
            "application/xml",
            $xmlRequest,
            new CircularReferenceException('circular reference found!'),
            'xml'
        ];
        yield [
            422,
            [
                'type' => 'ValidationException',
                'message' => 'A validation error occurred',
                'code' => 0,
                'errors' => ['pizza' => 'It has anchovy!'],
            ],
            "application/xml",
            $xmlRequest,
            new ValidationException(['pizza' => 'It has anchovy!']),
            'xml'
        ];

        $validator = $this->prophesize(Validator::class);
        $validator->errors()->willReturn(new MessageBag(['pizza' => 'It has anchovy!']));
        yield [
            422,
            [
                'type' => 'ValidationException',
                'message' => 'A validation error occurred',
                'code' => 0,
                'errors' => ['pizza' => 'It has anchovy!'],
            ],
            "application/xml",
            $xmlRequest,
            new LaravelValidationException($validator->reveal()),
            'xml'
        ];
    }

    /**
     * @dataProvider isApieActionProvider
     */
    public function testIsApieAction(bool $expected, Request $request)
    {
        $this->assertEquals($expected, resolve(ApieExceptionToResponse::class)->isApieAction($request));
    }

    public function isApieActionProvider()
    {
        yield [false, Request::create('this-is-sparta!')];
        $request = Request::create('this-is-martha!');
        $request->setRouteResolver(function () {
            $route = new Route(['get'], '/this-is-martha', []);
            return $route->name('not-apie');
        });
        yield [false, $request];
        $request = Request::create('user/3000');
        $request->setRouteResolver(function () {
            $route = new Route(['get'], '/user/{id}', []);
            return $route->name('apie.get');
        });
        yield [true, $request];
    }
}
