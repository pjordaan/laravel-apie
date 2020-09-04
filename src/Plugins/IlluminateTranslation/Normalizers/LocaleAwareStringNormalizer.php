<?php

namespace W2w\Laravel\Apie\Plugins\IlluminateTranslation\Normalizers;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\ValueObjects\Locale;
use W2w\Laravel\Apie\Plugins\IlluminateTranslation\ValueObjects\LocaleAwareString;
use W2w\Lib\ApieObjectAccessNormalizer\Interfaces\LocalizationAwareInterface;

class LocaleAwareStringNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @var LocalizationAwareInterface
     */
    private $localizationAware;

    public function __construct(LocalizationAwareInterface  $localizationAware)
    {
        $this->localizationAware = $localizationAware;
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if (is_string($data)) {
            $data = [
                $this->localizationAware->getContentLanguage() => $data
            ];
        }
        return LocaleAwareString::fromNative($data);
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === LocaleAwareString::class;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        /** @var LocaleAwareString $object */
        if ($format === 'datalayer') {
            return $object->jsonSerialize();
        }
        return $object->get(new Locale($this->localizationAware->getAcceptLanguage()));
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof LocaleAwareString;
    }
}
