<?php


namespace W2w\Laravel\Apie\Plugins\Illuminate\Normalizers;

use Illuminate\Support\Collection;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class CollectionNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if (array_key_exists('collection_resource', $context)) {
            unset($context['object_to_populate']);
            return new Collection($this->serializer->denormalize($data, $context['collection_resource'] . '[]', $format, $context));
        }
        return new Collection($data);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === Collection::class;
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
        return $data instanceof Collection;
    }
}
