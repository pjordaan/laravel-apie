<?php
namespace W2w\Laravel\Apie\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use W2w\Laravel\Apie\Providers\ApiResourceServiceProvider;
use W2w\Laravel\Apie\Tests\Mocks\DomainObjectThatRequireAuthorization;
use W2w\Laravel\Apie\Tests\Mocks\DomainObjectWithLaravelValidation;
use W2w\Laravel\Apie\Tests\Mocks\MockPolicy;
use W2w\Laravel\Apie\Tests\Mocks\MockSubAction;
use W2w\Lib\Apie\Plugins\ApplicationInfo\ApiResources\ApplicationInfo;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ValidationException;

class FeatureTest extends AbstractLaravelTestCase
{
    protected function getEnvironmentSetUp($application)
    {
        $config = $application->make('config');
        $config->set('app.name', __CLASS__);
        $actions = ['sub' => [MockSubAction::class]];
        $config->set('apie.subactions', $actions);
        $resources = [ApplicationInfo::class, DomainObjectWithLaravelValidation::class, DomainObjectThatRequireAuthorization::class];
        $config->set('apie.resources', $resources);
    }

    protected function getPackageProviders($app)
    {
        $dummy = new class($app) extends ServiceProvider
        {
            public function boot()
            {
                require __DIR__ . '/MockControllers/routing.php';
            }

            public function register()
            {
            }
        };
        $policy = new class($app) extends AuthServiceProvider
        {
            protected $policies = [
                DomainObjectThatRequireAuthorization::class => MockPolicy::class,
            ];

            public function boot()
            {
                $this->registerPolicies();
            }
        };

        return [get_class($dummy), get_class($policy), ApiResourceServiceProvider::class];
    }

    public function testApiResourceFacadeResponse_works_with_get_all()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/test-facade-response/application_info');
        $this->assertEquals('1 ' . ApplicationInfo::class . ' 104', $response->getContent());
    }

    public function testApiResourceFacadeResponse_works_with_id()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/test-facade-response/application_info/name');
        $this->assertEquals(ApplicationInfo::class . ' 102', $response->getContent());
    }

    public function testResource_typehint_works_with_id()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/test-resource-typehint/name');
        $this->assertEquals(__CLASS__, $response->getContent());
    }

    public function testResource_sub_action_works_with_arguments()
    {
        $this->withoutExceptionHandling();
        try {
            $response = $this->postJson(
                '/api/application_info/name/sub',
                ['additional_argument' => 42]
            );
            $response->assertJson(
                [
                    'app_name' => __CLASS__,
                    'additional_argument' => 42
                ]
            );
        } catch (ValidationException $validationException) {
            $this->fail('I did not expect a validation error with: ' . json_encode($validationException->getErrors()));
        }
    }

    public function testResource_sub_action_has_required_validations()
    {
        $this->withoutExceptionHandling();
        try {
            $response = $this->postJson(
                '/api/application_info/name/sub',
                []
            );
            $this->fail('I should have thrown a validation exception but got: ' . $response->getContent());
        } catch (ValidationException $validationException) {
            $this->assertEquals(['additional_argument' => ['required']], $validationException->getErrors());
        }
    }

    public function testResource_sub_action_has_type_check_validations()
    {
        $this->withoutExceptionHandling();
        try {
            $response = $this->postJson(
                '/api/application_info/name/sub',
                ['additional_argument' => 'not a number']
            );
            $this->fail('I should have thrown a validation exception but got: ' . $response->getContent());
        } catch (ValidationException $validationException) {
            $this->assertEquals(['additional_argument' => ['must be one of "int" ("not a number" given)']], $validationException->getErrors());
        }
    }

    public function testResource_use_laravel_validation()
    {
        $this->withoutExceptionHandling();
        try {
            $response = $this->postJson(
                '/api/domain_object_with_laravel_validation',
                ['one' => 'not a number']
            );
            $this->fail('I should have thrown a validation exception but got: ' . $response->getContent());
        } catch (LaravelValidationException $validationException) {
            $this->assertEquals(['one' => ['The one must be a number.'], 'two' => ['The two field is required.']], $validationException->errors());
        }
    }

    public function testResource_use_laravel_policy_not_logged_in()
    {
        $this->withoutExceptionHandling();
        $this->expectException(AuthorizationException::class);
        $this->postJson(
            '/api/domain_object_that_require_authorization',
            [
                'id' => '1',
                'one' => 'one',
                'two' => 'two',
            ]
        );
    }

    public function testResource_use_laravel_policy_logged_in()
    {
        $this->withoutExceptionHandling();
        $this->actingAs(new GenericUser([]));
        try {
            $response = $this->postJson(
                '/api/domain_object_that_require_authorization',
                [
                    'id'  => '11',
                    'one' => 'one',
                    'two' => 'two',
                ]
            );
            $this->fail('I should have thrown an authorization exception but got: ' . $response->getContent());
        } catch (AuthorizationException $authorizationException) {
            $this->assertInstanceOf(AuthorizationException::class, $authorizationException);
        };
        $validEntry = [
            'id'  => '1',
            'one' => 'one',
            'two' => 'two',
        ];
        $response = $this->postJson(
            '/api/domain_object_that_require_authorization',
            $validEntry
        );
        $response->assertExactJson(
            $validEntry
        );
        $response = $this->getJson(
            '/api/domain_object_that_require_authorization/1'
        );
        $response->assertExactJson(
            $validEntry
        );
        try {
            $response = $this->putJson(
                '/api/domain_object_that_require_authorization/1',
                $validEntry
            );
            $this->fail('I should have thrown an authorization exception but got: ' . $response->getContent());
        } catch (AuthorizationException $authorizationException) {
            $this->assertInstanceOf(AuthorizationException::class, $authorizationException);
        };
        $validPutEntry = [
            'id' => '11',
            'one' => 'one',
            'two' => 'two',
        ];
        $response = $this->putJson(
            '/api/domain_object_that_require_authorization/1',
            $validPutEntry
        );
        $response->assertExactJson(
            $validPutEntry
        );
    }
}
