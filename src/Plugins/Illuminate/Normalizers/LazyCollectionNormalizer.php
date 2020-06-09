<?php


namespace W2w\Laravel\Apie\Plugins\Illuminate\Normalizers;

use Illuminate\Support\LazyCollection;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class LazyCollectionNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if (array_key_exists('collection_resource', $context)) {
            unset($context['object_to_populate']);
            return LazyCollection::make(function () use (&$data, &$format, &$context) {
                foreach ($data as $key => $value) {
                    yield $key => $this->serializer->denormalize($value, $context['collection_resource'], $format, $context);
                }
            });
        }
        return LazyCollection::make(function () use (&$data) {
            yield from $data;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === LazyCollection::class;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $result = [];
        foreach ($object as $value) {
            $result[] = $this->serializer->normalize($value, $format, $context);
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof LazyCollection;
    }
}
