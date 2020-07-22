<?php


namespace W2w\Laravel\Apie\Plugins\IlluminateTranslation\Normalizers;

use Illuminate\Contracts\Translation\Translator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;
use W2w\Lib\Apie\Plugins\Core\Normalizers\ExceptionNormalizer;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\LocalizationableException;

class LocationableExceptionNormalizer implements NormalizerInterface
{
    use SharedTranslatorTrait;

    /**
     * @var ExceptionNormalizer
     */
    private $exceptionNormalizer;

    public function __construct(ExceptionNormalizer $exceptionNormalizer, Translator $translator)
    {
        $this->exceptionNormalizer = $exceptionNormalizer;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        /** @var LocalizationableException|Throwable $object */
        $data = $this->exceptionNormalizer->normalize($object, $format, $context);
        $data['message'] = $this->translate($object->getI18n());
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $this->exceptionNormalizer->supportsNormalization($data, $format) && $data instanceof LocalizationableException;
    }
}
