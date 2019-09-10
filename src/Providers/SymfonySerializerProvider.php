<?php

namespace W2w\Laravel\Apie\Providers;

use Illuminate\Foundation\Application;
use Carbon\CarbonInterface;
use Doctrine\Common\Annotations\Reader;
use GBProd\UuidNormalizer\UuidDenormalizer;
use GBProd\UuidNormalizer\UuidNormalizer;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\ServiceProvider;
use Madewithlove\IlluminatePsrCacheBridge\Laravel\CacheItemPool;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\Extractor\SerializerExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Mapping\Loader\LoaderChain;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use W2w\Lib\Apie\BaseGroupLoader;
use W2w\Lib\Apie\Normalizers\ContextualNormalizer;
use W2w\Lib\Apie\Normalizers\EvilReflectionPropertyNormalizer;
use W2w\Lib\Apie\Normalizers\ExceptionNormalizer;
use W2w\Lib\Apie\Normalizers\StringValueObjectNormalizer;


class SymfonySerializerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        if (config('app.debug')) {
            $this->app->singleton('serializer-cache', function () {
                return new ArrayAdapter(0, true);
            });
        } else {
            $this->app->singleton('serializer-cache', function (Application $app) {
                $repository = $app->make(Repository::class);

                return new CacheItemPool($repository);
            });
        }

        $this->app->singleton(PropertyAccessor::class, function (Application $app) {
            return PropertyAccess::createPropertyAccessorBuilder()
                ->setCacheItemPool($app->make('serializer-cache'))
                ->getPropertyAccessor();
        });

        $this->app->singleton(CamelCaseToSnakeCaseNameConverter::class);
        $this->app->alias(CamelCaseToSnakeCaseNameConverter::class, NameConverterInterface::class);

        $this->app->singleton(PropertyInfoExtractor::class, function (Application $app) {
            $factory = $app->get(ClassMetadataFactory::class);
            $reflectionExtractor = new ReflectionExtractor();
            $phpDocExtractor = new PhpDocExtractor();

            return new PropertyInfoExtractor(
                [
                    new SerializerExtractor($factory),
                    $reflectionExtractor,
                ],
                [
                    $phpDocExtractor,
                    $reflectionExtractor,
                ],
                [
                    $phpDocExtractor,
                ],
                [
                    $reflectionExtractor,
                ],
                [
                    $reflectionExtractor,
                ]
            );
        });
        $this->app->alias(PropertyInfoExtractor::class, PropertyTypeExtractorInterface::class);

        $this->app->singleton(ClassMetadataFactory::class, function () {
            return new ClassMetadataFactory(
                new LoaderChain([
                    new AnnotationLoader($this->app->get(Reader::class)),
                    new BaseGroupLoader(['read', 'write', 'get', 'post', 'put']),
                ]),
                null
            );
        });
        $this->app->alias(ClassMetadataFactory::class, ClassMetadataFactoryInterface::class);

        $this->app->singleton(SerializerInterface::class, function () {
            $classDiscriminator = new ClassDiscriminatorFromClassMetadata($this->app->get(ClassMetadataFactoryInterface::class));

            $objectNormalizer = new ObjectNormalizer(
                $this->app->get(ClassMetadataFactoryInterface::class),
                $this->app->get(NameConverterInterface::class),
                $this->app->get(PropertyAccessor::class),
                $this->app->get(PropertyTypeExtractorInterface::class),
                $classDiscriminator,
                null,
                []
            );
            $evilObjectNormalizer = new EvilReflectionPropertyNormalizer(
                $this->app->get(ClassMetadataFactoryInterface::class),
                $this->app->get(NameConverterInterface::class),
                $this->app->get(PropertyAccessor::class),
                $this->app->get(PropertyTypeExtractorInterface::class),
                $classDiscriminator,
                null,
                []
            );
            $encoders = [
                new XmlEncoder([XmlEncoder::ROOT_NODE_NAME => 'item']),
                new JsonEncoder(
                    new JsonEncode([JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES]),
                    new JsonDecode([JsonDecode::ASSOCIATIVE => false])
                )];
            $normalizers = [
                new UuidNormalizer(),
                new UuidDenormalizer(),
                new ExceptionNormalizer(config('app.debug')),
                new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => CarbonInterface::DEFAULT_TO_STRING_FORMAT]),
                new StringValueObjectNormalizer(),
                new JsonSerializableNormalizer(),
                new ArrayDenormalizer(),
                new ContextualNormalizer([$evilObjectNormalizer]),
                $objectNormalizer,
            ];
            foreach ($this->app->tagged(NormalizerInterface::class) as $normalizer) {
                array_unshift($normalizers, $normalizer);
            }
            ContextualNormalizer::disableDenormalizer(EvilReflectionPropertyNormalizer::class);
            ContextualNormalizer::disableNormalizer(EvilReflectionPropertyNormalizer::class);

            return new Serializer($normalizers, $encoders);
        });

        $this->app->alias(SerializerInterface::class, NormalizerInterface::class);
        $this->app->alias(SerializerInterface::class, DenormalizerInterface::class);
    }
}
