<?php
namespace W2w\Laravel\Apie\Providers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Redis\LimiterTimeoutException;
use PDOException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use W2w\Lib\Apie\Annotations\ApiResource;
use W2w\Lib\Apie\Resources\ApiResourcesInterface;

class ApieConfigResolver
{
    private static $configResolver;

    public static function resolveConfig(array $config)
    {
        $resolver = self::getResolver();
        return $resolver->resolve($config);
    }

    private static function getResolver(): OptionsResolver
    {
        if (!self::$configResolver) {
            $resolver = new OptionsResolver();
            $defaults = require __DIR__ . '/../../config/apie.php';
            $resolver->setDefaults($defaults)
                ->setAllowedTypes('resources', ['string[]', ApiResourcesInterface::class])
                ->setAllowedTypes('resources-service', ['null', 'string'])
                ->setAllowedTypes('mock', ['null', 'bool'])
                ->setAllowedTypes('mock-skipped-resources', ['string[]'])
                ->setAllowedTypes('base-url', 'string')
                ->setAllowedTypes('api-url', 'string')
                ->setAllowedTypes('disable-routes', ['null', 'bool'])
                ->setAllowedTypes('swagger-ui-test-page', ['null', 'string'])
                ->setAllowedTypes('apie-middleware', 'string[]')
                ->setAllowedTypes('swagger-ui-test-page-middleware', 'string[]')
                ->setAllowedTypes('bind-api-resource-facade-response', 'bool')
                ->setAllowedTypes('metadata', 'string[]')
                ->setAllowedTypes('resource-config', [ApiResource::class . '[]', 'array[]'])
                ->setAllowedTypes('exception-mapping', 'int[]');
            $resolver->setDefault('metadata', function (OptionsResolver $metadataResolver) use (&$defaults) {
                $metadataResolver->setDefaults($defaults['metadata']);

                $urlNormalizer = function (Options $options, $value) {
                    if (empty($value)) {
                        return '';
                    }
                    return self::urlNormalize($value);
                };
                $metadataResolver->setNormalizer('terms-of-service', $urlNormalizer);
                $metadataResolver->setNormalizer('license-url', $urlNormalizer);
                $metadataResolver->setNormalizer('contact-url', $urlNormalizer);
            });
            $resolver->setNormalizer('resource-config', function (Options $options, $value) {
                return array_map(function ($field) {
                    return $field instanceof ApiResource ? $field : ApiResource::createFromArray($field);
                }, $value);
            });
            $resolver->setNormalizer('exception-mapping', function (Options $options, $value) {
                ApieConfigResolver::addExceptionsForExceptionMapping($value);
                return $value;
            });
            self::$configResolver = $resolver;
        }
        return self::$configResolver;
    }

    public static function addExceptionsForExceptionMapping(array& $array)
    {
        $array[AuthorizationException::class] = 403;
        $array[AuthenticationException::class] = 401;
        $array[UnexpectedValueException::class] = 415;
        $array[RuntimeException::class] = 415;
        $array[LockTimeoutException::class] = 502;
        $array[LimiterTimeoutException::class] = 502;
        $array[FileNotFoundException::class] = 502;
        $array[PDOException::class] = 502;
        return $array;
    }

    private static function urlNormalize($value)
    {
        if ('http://' !== substr($value, 0, 7) && 'https://' !== substr($value, 0, 8)) {
            $value = 'https://'.$value;
        }
        $parsedUrl = parse_url($value);
        if (empty($parsedUrl) || !in_array($parsedUrl['scheme'], ['http', 'https']) || !filter_var($value, FILTER_VALIDATE_URL)) {
            throw new InvalidOptionsException('"' . $value . '" is not a valid url');
        }

        return $value;
    }
}
