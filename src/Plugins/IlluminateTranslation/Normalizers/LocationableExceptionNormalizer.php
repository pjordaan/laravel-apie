<?php


namespace W2w\Laravel\Apie\Plugins\IlluminateTranslation\Normalizers;

use Illuminate\Contracts\Translation\Translator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;
use W2w\Lib\Apie\Plugins\Core\Normalizers\ExceptionNormalizer;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\LocalizationableException;

class LocationableExceptionNormalizer implements NormalizerInterface
{
    /**
     * @var ExceptionNormalizer
     */
    private $exceptionNormalizer;

    /**
     * @var Translator
     */
    private $translator;

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
        $i18n = $object->getI18n();
        $replacements = [];
        foreach ($i18n->getReplacements() as $key => $value) {
            if (is_array($value)) {
                $replacements[$key] = json_encode($value);
            } else {
                $replacements[$key] = $value;
            }
        }
        $data['message'] = $this->translator->choice(
            'apie::' . $i18n->getMessageString(),
            $i18n->getAmount(),
            $replacements
        );
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
