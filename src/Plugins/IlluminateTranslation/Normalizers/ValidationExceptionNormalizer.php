<?php


namespace W2w\Laravel\Apie\Plugins\IlluminateTranslation\Normalizers;

use Illuminate\Contracts\Translation\Translator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use W2w\Lib\ApieObjectAccessNormalizer\Errors\ErrorBagField;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ValidationException;

class ValidationExceptionNormalizer implements NormalizerInterface
{
    use SharedTranslatorTrait;

    /**
     * @var LocationableExceptionNormalizer
     */
    private $locationableExceptionNormalizer;

    public function __construct(LocationableExceptionNormalizer $locationableExceptionNormalizer, Translator $translator)
    {
        $this->locationableExceptionNormalizer = $locationableExceptionNormalizer;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        /** @var ValidationException $object */
        $data = $this->locationableExceptionNormalizer->normalize($object, $format, $context);
        $data['errors'] = $object->getErrorBag()->getErrors(
            function (ErrorBagField $field) {
                $info = $field->getLocalizationInfo();
                if (!$info) {
                    return $field->getMessage();
                }
                return $this->translate($info);
            }
        );
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof ValidationException;
    }
}
