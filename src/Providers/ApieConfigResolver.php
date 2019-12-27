<?php
namespace W2w\Laravel\Apie\Providers;

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
                ->setAllowedTypes('metadata', 'string[]');
            $resolver->setDefault('metadata', function (OptionsResolver $metadataResolver) use (&$defaults) {
                $metadataResolver->setDefaults($defaults['metadata']);

                $urlNormalizer = function (Options $options, $value) {
                    return self::urlNormalize($value);
                };
                $metadataResolver->setNormalizer('terms-of-service', $urlNormalizer);
                $metadataResolver->setNormalizer('license-url', $urlNormalizer);
                $metadataResolver->setNormalizer('contact-url', $urlNormalizer);
            });
            self::$configResolver = $resolver;
        }
        return self::$configResolver;
    }

    private static function urlNormalize($value)
    {
        if ($value === '') {
            return '';
        }
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
